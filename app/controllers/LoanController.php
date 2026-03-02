<?php
// app/controllers/LoanController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\{Loan, Client, User};
use App\Services\LoanCalculator\CalculatorFactory;

class LoanController extends Controller
{
    // LIST
    public function index(): void
    {
        $filters = [
            'search'      => $this->get('search', ''),
            'status'      => $this->get('status', ''),
            'loan_type'   => $this->get('loan_type', ''),
            'assigned_to' => Auth::role() === 'asesor' ? Auth::id() : $this->get('assigned_to', ''),
            'filter'      => $this->get('filter', ''),
        ];
        $perPage  = (int)setting('items_per_page', 20);
        $page     = max(1, (int)$this->get('page', 1));
        $paged    = Loan::all($filters, $perPage, $page);
        $advisors = User::allAdvisors();

        $this->render('loans/index', [
            'title'   => 'Préstamos',
            'paged'   => $paged,
            'filters' => $filters,
            'advisors'=> $advisors,
        ]);
    }

    // SHOW
    public function show(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $installments = Loan::getInstallments((int)$id);
        $payments     = Loan::getPayments((int)$id);

        $calculator   = CalculatorFactory::make($loan['loan_type']);
        $currentState = $calculator->calculateCurrentInterest($loan, new \DateTime());

        $aval      = !empty($loan['aval_id']) ? Client::findAvalById((int)$loan['aval_id']) : null;
        $guarantee = DB::row("SELECT * FROM loan_guarantees WHERE loan_id = ? LIMIT 1", [(int)$id]);

        $this->render('loans/show', [
            'title'        => $loan['loan_number'],
            'loan'         => $loan,
            'installments' => $installments,
            'payments'     => $payments,
            'currentState' => $currentState,
            'aval'         => $aval,
            'guarantee'    => $guarantee,
        ]);
    }

    // CREATE WIZARD
    public function create(): void
    {
        $clientId      = $this->get('client_id', '');
        $client        = $clientId ? Client::find((int)$clientId) : null;
        $clients       = DB::all("SELECT id, code, CONCAT(first_name,' ',last_name) as full_name FROM clients WHERE is_active=1 ORDER BY last_name");
        $advisors      = User::allAdvisors();
        $avalThreshold = (float)setting('aval_required_amount', 10000);
        $aval          = $client ? Client::getAval((int)$client['id']) : null;
        $defaults      = [
            'late_fee_rate'      => setting('default_late_fee_rate', 0.05),
            'grace_days'         => setting('grace_days', 3),
            'disbursement_date'  => date('Y-m-d'),
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ];

        $this->render('loans/create', [
            'title'         => 'Nuevo Préstamo',
            'clients'       => $clients,
            'client'        => $client,
            'advisors'      => $advisors,
            'defaults'      => $defaults,
            'aval'          => $aval,
            'avalThreshold' => $avalThreshold,
        ]);
    }

    // STORE
    public function store(): void
    {
        CSRF::check();
        $data = $_POST;

        $rules = [
            'client_id'          => 'required|numeric|min_val:1',
            'loan_type'          => 'required|in:A,B,C',
            'principal'          => 'required|numeric|min_val:1',
            'interest_rate'      => 'required|numeric|min_val:0',
            'disbursement_date'  => 'required|date',
            'first_payment_date' => 'required|date',
        ];
        if ($data['loan_type'] === 'A' || $data['loan_type'] === 'C') {
            $rules['term_months'] = 'required|numeric|min_val:1|max_val:600';
        }

        $v = Validator::make($data, $rules);
        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/loans/create?client_id=' . ($data['client_id'] ?? ''));
        }

        $data['term_months']       = isset($data['term_months']) && $data['term_months'] !== '' ? (int)$data['term_months'] : null;
        $data['assigned_to']       = isset($data['assigned_to']) && $data['assigned_to'] !== '' ? (int)$data['assigned_to'] : null;
        $data['maturity_date']     = isset($data['maturity_date']) && $data['maturity_date'] !== '' ? $data['maturity_date'] : null;
        $data['notes']             = trim($data['notes'] ?? '') ?: null;
        $data['payment_frequency'] = $data['payment_frequency'] ?? 'monthly';

        $rate = (float)$data['interest_rate'] / 100;
        $data['interest_rate'] = $rate;
        $data['late_fee_rate'] = !empty($data['late_fee_rate']) ? (float)$data['late_fee_rate'] / 100 : (float)setting('default_late_fee_rate', 0.05);

        DB::beginTransaction();
        try {
            $loanId = Loan::create($data, Auth::id());

            $calculator = CalculatorFactory::make($data['loan_type']);
            $schedule   = $calculator->buildSchedule(array_merge($data, ['interest_rate' => $rate]));

            $validInstCols = [
                'installment_number','due_date','principal_amount','interest_amount',
                'total_amount','balance_after','paid_amount','paid_principal',
                'paid_interest','paid_late_fee','late_fee','days_late','status',
            ];

            foreach ($schedule['installments'] as $inst) {
                $row = array_intersect_key($inst, array_flip($validInstCols));
                $row['loan_id'] = $loanId;
                DB::insert('loan_installments', $row);
            }

            if (!empty($schedule['installments'])) {
                $last = end($schedule['installments']);
                DB::update('loans', ['maturity_date' => $last['due_date']], 'id = ?', [$loanId]);
            }

            // Aval
            if (!empty($data['aval_id'])) {
                DB::update('loans', ['aval_id' => (int)$data['aval_id']], 'id = ?', [$loanId]);
            }

            // Garantía
            if (!empty($data['has_guarantee']) && !empty($data['guarantee_type'])) {
                $gDesc = $data['g_description'] ?? '';
                if (!$gDesc && $data['guarantee_type'] === 'vehiculo') {
                    $gDesc = trim(($data['g_brand'] ?? '') . ' ' . ($data['g_model'] ?? '') . ' ' . ($data['g_year'] ?? ''));
                }
                DB::insert('loan_guarantees', [
                    'loan_id'         => $loanId,
                    'guarantee_type'  => $data['guarantee_type'],
                    'description'     => $gDesc,
                    'brand'           => $data['g_brand']           ?? null,
                    'model'           => $data['g_model']           ?? null,
                    'year'            => $data['g_year']            ?? null,
                    'plate'           => $data['g_plate']           ?? null,
                    'serial'          => $data['g_serial']          ?? null,
                    'color'           => $data['g_color']           ?? null,
                    'estimated_value' => !empty($data['g_estimated_value']) ? (float)$data['g_estimated_value'] : null,
                ]);
                DB::update('loans', ['has_guarantee' => 1], 'id = ?', [$loanId]);
            }

            DB::insert('loan_events', [
                'loan_id'     => $loanId,
                'user_id'     => Auth::id(),
                'event_type'  => 'created',
                'description' => 'Préstamo creado. Monto: ' . $data['principal'] . ' · Tipo: ' . $data['loan_type'],
                'meta'        => json_encode($schedule),
            ]);

            DB::insert('audit_log', [
                'user_id'    => Auth::id(), 'action' => 'create',
                'entity'     => 'loans', 'entity_id' => $loanId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            DB::commit();
            $this->flashRedirect("/loans/$loanId", 'success', 'Préstamo creado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollback();
            error_log('[LoanController] ' . $e->getMessage());
            View::flash('error', 'Error al crear el préstamo: ' . $e->getMessage());
            $this->redirect('/loans/create');
        }
    }

    // EDIT
    public function edit(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');
        $advisors = User::allAdvisors();

        $this->render('loans/edit', [
            'title'    => 'Editar: ' . $loan['loan_number'],
            'loan'     => $loan,
            'advisors' => $advisors,
        ]);
    }

    // UPDATE
    public function update(string $id): void
    {
        CSRF::check();
        $allowed = ['notes','assigned_to','status','late_fee_rate','grace_days'];
        $data    = array_intersect_key($_POST, array_flip($allowed));
        if (isset($data['late_fee_rate'])) $data['late_fee_rate'] = (float)$data['late_fee_rate'] / 100;

        DB::update('loans', $data, 'id = ?', [(int)$id]);
        $this->flashRedirect("/loans/$id", 'success', 'Préstamo actualizado.');
    }

    // DESTROY
    public function destroy(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $loan = Loan::find((int)$id);
        if (!$loan) $this->flashRedirect('/loans', 'error', 'Préstamo no encontrado.');

        if ($loan['status'] === 'active') {
            DB::update('loans', ['status' => 'cancelled'], 'id = ?', [(int)$id]);
            DB::insert('loan_events', [
                'loan_id'     => (int)$id,
                'user_id'     => Auth::id(),
                'event_type'  => 'cancelled',
                'description' => 'Préstamo cancelado por ' . Auth::user()['name'],
                'meta'        => json_encode(['previous_status' => 'active']),
            ]);
            $this->flashRedirect('/loans', 'success', 'Préstamo ' . $loan['loan_number'] . ' cancelado.');
        } else {
            $hasPayments = DB::row("SELECT COUNT(*) as n FROM payments WHERE loan_id = ? AND voided = 0", [(int)$id]);
            if ($hasPayments['n'] > 0) {
                $this->flashRedirect('/loans', 'error', 'No se puede eliminar un préstamo con pagos registrados. Cancélelo primero.');
            }
            DB::delete('loan_installments', 'loan_id = ?', [(int)$id]);
            DB::delete('loan_events',       'loan_id = ?', [(int)$id]);
            DB::delete('loans',             'id = ?',      [(int)$id]);
            $this->flashRedirect('/loans', 'success', 'Préstamo ' . $loan['loan_number'] . ' eliminado.');
        }
    }

    // AMORTIZATION TABLE (sin layout)
    public function amortization(string $id): void
    {
        $loan         = Loan::find((int)$id);
        $installments = Loan::getInstallments((int)$id);

        $calculator   = CalculatorFactory::make($loan['loan_type']);
        $currentState = $calculator->calculateCurrentInterest($loan, new \DateTime());

        $this->render('loans/amortization', [
            'title'        => 'Tabla de Amortización · ' . $loan['loan_number'],
            'loan'         => $loan,
            'installments' => $installments,
            'currentState' => $currentState,
        ], null);
    }

    // PAGARÉ (sin layout — página imprimible)
    public function pagare(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $client = Client::find((int)$loan['client_id']);
        $aval   = !empty($loan['aval_id']) ? Client::findAvalById((int)$loan['aval_id']) : null;

        $this->render('documents/pagare', [
            'title'  => 'Pagaré · ' . $loan['loan_number'],
            'loan'   => $loan,
            'client' => $client,
            'aval'   => $aval,
        ], null);
    }

    // CONTRATO (sin layout — página imprimible)
    public function contrato(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $client    = Client::find((int)$loan['client_id']);
        $aval      = !empty($loan['aval_id']) ? Client::findAvalById((int)$loan['aval_id']) : null;
        $guarantee = DB::row("SELECT * FROM loan_guarantees WHERE loan_id = ? LIMIT 1", [(int)$id]);

        $this->render('documents/contrato', [
            'title'     => 'Contrato · ' . $loan['loan_number'],
            'loan'      => $loan,
            'client'    => $client,
            'aval'      => $aval,
            'guarantee' => $guarantee,
        ], null);
    }

    
}