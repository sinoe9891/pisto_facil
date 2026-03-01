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
        $perPage = (int)setting('items_per_page', 20);
        $page    = max(1, (int)$this->get('page', 1));
        $paged   = Loan::all($filters, $perPage, $page);
        $advisors = User::allAdvisors();

        // ── Enriquecer cada préstamo con info de mora/próximo vencimiento ──────
        // Una sola query para todos los préstamos de la página
        $loanIds = array_column($paged['data'], 'id');
        $overdueMap = [];

        if (!empty($loanIds)) {
            $placeholders = implode(',', array_fill(0, count($loanIds), '?'));
            $instInfo = DB::all(
                "SELECT
                    loan_id,
                    MIN(CASE WHEN status IN ('pending','partial') THEN due_date END) AS next_due_date,
                    SUM(CASE WHEN due_date < CURDATE() AND status IN ('pending','partial') THEN 1 ELSE 0 END) AS overdue_count,
                    MIN(CASE WHEN due_date < CURDATE() AND status IN ('pending','partial') THEN due_date END) AS oldest_overdue_date,
                    COALESCE(SUM(CASE WHEN status IN ('pending','partial') THEN total_amount - paid_amount ELSE 0 END),0) AS pending_amount
                 FROM loan_installments
                 WHERE loan_id IN ($placeholders)
                 GROUP BY loan_id",
                $loanIds
            );
            foreach ($instInfo as $r) {
                $overdueMap[$r['loan_id']] = $r;
            }
        }

        $today = date('Y-m-d');
        foreach ($paged['data'] as &$l) {
            $info = $overdueMap[$l['id']] ?? null;
            $l['next_due_date']      = $info['next_due_date'] ?? null;
            $l['overdue_count']      = (int)($info['overdue_count'] ?? 0);
            $l['oldest_overdue_date']= $info['oldest_overdue_date'] ?? null;
            $l['pending_amount']     = (float)($info['pending_amount'] ?? 0);

            // Calcular días de mora (desde la cuota más antigua vencida)
            if ($l['oldest_overdue_date']) {
                $diff = (new \DateTime($l['oldest_overdue_date']))->diff(new \DateTime($today));
                $l['days_overdue'] = (int)$diff->days;
            } else {
                $l['days_overdue'] = 0;
            }

            // Calcular días para vencer (próxima cuota)
            if ($l['next_due_date'] && $l['next_due_date'] >= $today) {
                $diff = (new \DateTime($today))->diff(new \DateTime($l['next_due_date']));
                $l['days_until_due'] = (int)$diff->days;
            } else {
                $l['days_until_due'] = null;
            }
        }
        unset($l);

        // Contadores para los botones de filtro rápido
        $counts = DB::row(
            "SELECT
                COUNT(*) as total_active,
                SUM(CASE WHEN EXISTS(
                    SELECT 1 FROM loan_installments li
                    WHERE li.loan_id = l.id AND li.due_date < CURDATE() AND li.status IN ('pending','partial')
                ) THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN EXISTS(
                    SELECT 1 FROM loan_installments li
                    WHERE li.loan_id = l.id AND li.due_date >= CURDATE()
                      AND li.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      AND li.status IN ('pending','partial')
                      AND NOT EXISTS(SELECT 1 FROM loan_installments li2
                        WHERE li2.loan_id = l.id AND li2.due_date < CURDATE() AND li2.status IN ('pending','partial'))
                ) THEN 1 ELSE 0 END) as upcoming_count
             FROM loans l WHERE l.status = 'active'" .
            (Auth::role() === 'asesor' ? " AND l.assigned_to = " . (int)Auth::id() : '')
        );

        $this->render('loans/index', [
            'title'    => 'Préstamos',
            'paged'    => $paged,
            'filters'  => $filters,
            'advisors' => $advisors,
            'counts'   => $counts,
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

        $this->render('loans/show', [
            'title'        => $loan['loan_number'],
            'loan'         => $loan,
            'installments' => $installments,
            'payments'     => $payments,
            'currentState' => $currentState,
        ]);
    }

    // CREATE WIZARD
    public function create(): void
    {
        $clientId = $this->get('client_id', '');
        $client   = $clientId ? Client::find((int)$clientId) : null;
        $clients  = DB::all("SELECT id, code, CONCAT(first_name,' ',last_name) as full_name FROM clients WHERE is_active=1 ORDER BY last_name");
        $advisors = User::allAdvisors();
        $defaults = [
            'late_fee_rate'      => setting('default_late_fee_rate', 0.05),
            'grace_days'         => setting('grace_days', 3),
            'disbursement_date'  => date('Y-m-d'),
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ];

        $this->render('loans/create', [
            'title'    => 'Nuevo Préstamo',
            'clients'  => $clients,
            'client'   => $client,
            'advisors' => $advisors,
            'defaults' => $defaults,
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

        $data['term_months']    = isset($data['term_months'])   && $data['term_months']   !== '' ? (int)$data['term_months']   : null;
        $data['assigned_to']    = isset($data['assigned_to'])   && $data['assigned_to']   !== '' ? (int)$data['assigned_to']   : null;
        $data['maturity_date']  = isset($data['maturity_date']) && $data['maturity_date'] !== '' ? $data['maturity_date']      : null;
        $data['notes']             = trim($data['notes'] ?? '') ?: null;
        $data['payment_frequency'] = $data['payment_frequency'] ?? 'monthly';

        $rate     = (float)$data['interest_rate'] / 100;
        $rateType = $data['rate_type'] ?? 'monthly';

        $data['interest_rate'] = $rate;
        $data['late_fee_rate'] = !empty($data['late_fee_rate']) ? (float)$data['late_fee_rate'] / 100 : (float)setting('default_late_fee_rate', 0.05);

        DB::beginTransaction();
        try {
            $loanId     = Loan::create($data, Auth::id());
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

            DB::insert('loan_events', [
                'loan_id'     => $loanId,
                'user_id'     => Auth::id(),
                'event_type'  => 'created',
                'description' => 'Préstamo creado. Monto: ' . $data['principal'] . ' · Tipo: ' . $data['loan_type'],
                'meta'        => json_encode($schedule),
            ]);

            DB::insert('audit_log', [
                'user_id'    => Auth::id(),
                'action'     => 'create',
                'entity'     => 'loans',
                'entity_id'  => $loanId,
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
        $loan     = Loan::find((int)$id);
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
        if (!$loan) {
            $this->flashRedirect('/loans', 'error', 'Préstamo no encontrado.');
        }

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
                $this->flashRedirect('/loans', 'error', 'No se puede eliminar un préstamo con pagos. Cancélelo primero.');
            }
            DB::delete('loan_installments', 'loan_id = ?', [(int)$id]);
            DB::delete('loan_events',       'loan_id = ?', [(int)$id]);
            DB::delete('loans',             'id = ?',      [(int)$id]);
            $this->flashRedirect('/loans', 'success', 'Préstamo ' . $loan['loan_number'] . ' eliminado.');
        }
    }

    // AMORTIZATION TABLE (printable) — ahora incluye currentState para Tipo C
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
}