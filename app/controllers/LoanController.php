<?php

/**
 * APP: app/controllers/LoanController.php
 * VERSIÓN: Con edición completa, recálculo de cuotas y refinanciamiento
 */

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\{Loan, Client, User};
use App\Services\LoanCalculator\CalculatorFactory;

class LoanController extends Controller
{
    /**
     * LISTA DE PRÉSTAMOS
     */
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
            'title'    => 'Préstamos',
            'paged'    => $paged,
            'filters'  => $filters,
            'advisors' => $advisors,
        ]);
    }

    /**
     * VER DETALLE DE PRÉSTAMO
     */
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

    /**
     * FORMULARIO DE CREACIÓN
     */
    public function create(): void
    {
        $clientId = $this->get('client_id', '');
        $client   = $clientId ? Client::find((int)$clientId) : null;
        $clients  = DB::all(
            "SELECT id, code, CONCAT(first_name,' ',last_name) as full_name
             FROM clients WHERE is_active=1 ORDER BY last_name"
        );
        $advisors = User::allAdvisors();
        $defaults = [
            'late_fee_rate'      => setting('default_late_fee_rate', 0.021),
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

    /**
     * GUARDAR NUEVO PRÉSTAMO
     */
    public function store(): void
    {
        CSRF::check();
        $data = $_POST;

        // ─── VALIDACIONES ──────────────────────────────────────────────
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

        // ─── NORMALIZAR DATOS ──────────────────────────────────────────
        $data['term_months']       = isset($data['term_months']) && $data['term_months'] !== ''
            ? (int)$data['term_months'] : null;
        $data['assigned_to']       = isset($data['assigned_to']) && $data['assigned_to'] !== ''
            ? (int)$data['assigned_to'] : null;
        $data['notes']             = trim($data['notes'] ?? '') ?: null;
        $data['payment_frequency'] = $data['payment_frequency'] ?? 'monthly';

        $rate                  = (float)$data['interest_rate'] / 100;
        $data['interest_rate'] = $rate;
        $data['late_fee_rate'] = !empty($data['late_fee_rate'])
            ? (float)$data['late_fee_rate'] / 100
            : (float)setting('default_late_fee_rate', 0.021);

        // ─── MÉTODOS DE PAGO ───────────────────────────────────────────
        $data['payment_method_cash']     = isset($_POST['payment_method_cash'])     ? 1 : 0;
        $data['payment_method_transfer'] = isset($_POST['payment_method_transfer']) ? 1 : 0;
        $data['payment_method_check']    = isset($_POST['payment_method_check'])    ? 1 : 0;
        $data['payment_method_atm']      = isset($_POST['payment_method_atm'])      ? 1 : 0;

        DB::beginTransaction();
        try {
            // ─── CREAR PRÉSTAMO ────────────────────────────────────────
            $loanId = Loan::create($data, Auth::id());

            // ─── GENERAR TABLA DE AMORTIZACIÓN ────────────────────────
            $calculator = CalculatorFactory::make($data['loan_type']);
            $schedule   = $calculator->buildSchedule(array_merge($data, ['interest_rate' => $rate]));

            $validInstCols = [
                'installment_number',
                'due_date',
                'principal_amount',
                'interest_amount',
                'total_amount',
                'balance_after',
                'paid_amount',
                'paid_principal',
                'paid_interest',
                'paid_late_fee',
                'late_fee',
                'days_late',
                'status',
            ];

            foreach ($schedule['installments'] as $inst) {
                $row = array_intersect_key($inst, array_flip($validInstCols));
                $row['loan_id'] = $loanId;
                DB::insert('loan_installments', $row);
            }

            // Actualizar fecha de vencimiento
            if (!empty($schedule['installments'])) {
                $last = end($schedule['installments']);
                DB::update('loans', ['maturity_date' => $last['due_date']], 'id = ?', [$loanId]);
            }

            // ─── AVAL ──────────────────────────────────────────────────
            if (!empty($data['aval_id'])) {
                DB::update('loans', ['aval_id' => (int)$data['aval_id']], 'id = ?', [$loanId]);
            }

            // ─── GARANTÍA ──────────────────────────────────────────────
            if (!empty($data['has_guarantee']) && !empty($data['guarantee_type'])) {
                $gDesc = $data['g_description'] ?? '';
                if (!$gDesc && $data['guarantee_type'] === 'vehiculo') {
                    $gDesc = trim(($data['g_brand'] ?? '') . ' ' . ($data['g_model'] ?? '') . ' ' . ($data['g_year'] ?? ''));
                }
                DB::insert('loan_guarantees', [
                    'loan_id'         => $loanId,
                    'guarantee_type'  => $data['guarantee_type'],
                    'description'     => $gDesc,
                    'brand'           => $data['g_brand']          ?? null,
                    'model'           => $data['g_model']          ?? null,
                    'year'            => $data['g_year']           ?? null,
                    'plate'           => $data['g_plate']          ?? null,
                    'serial'          => $data['g_serial']         ?? null,
                    'color'           => $data['g_color']          ?? null,
                    'estimated_value' => !empty($data['g_estimated_value'])
                        ? (float)$data['g_estimated_value'] : null,
                ]);
                DB::update('loans', ['has_guarantee' => 1], 'id = ?', [$loanId]);
            }

            // ─── REGISTRAR EVENTO ──────────────────────────────────────
            DB::insert('loan_events', [
                'loan_id'     => $loanId,
                'user_id'     => Auth::id(),
                'event_type'  => 'created',
                'description' => 'Préstamo creado. Monto: ' . $data['principal'] . ' · Tipo: ' . $data['loan_type'],
                'meta'        => json_encode($schedule),
            ]);

            // ─── AUDITORÍA ─────────────────────────────────────────────
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
            error_log('[LoanController::store] ' . $e->getMessage());
            View::flash('error', 'Error al crear el préstamo: ' . $e->getMessage());
            $this->redirect('/loans/create');
        }
    }

    /**
     * FORMULARIO DE EDICIÓN — Solo Admin/SuperAdmin
     */
    public function edit(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);

        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $installments = Loan::getInstallments((int)$id);
        $advisors     = User::allAdvisors();

        $this->render('loans/edit', [
            'title'        => 'Editar: ' . $loan['loan_number'],
            'loan'         => $loan,
            'advisors'     => $advisors,
            'installments' => $installments,
        ]);
    }

    /**
     * ACTUALIZAR PRÉSTAMO — Solo Admin/SuperAdmin
     * Soporta recálculo completo de cuotas si recalculate_installments=1
     */
    public function update(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $loan = Loan::find((int)$id);
        if (!$loan) $this->flashRedirect('/loans', 'error', 'Préstamo no encontrado.');

        $recalculate = ($_POST['recalculate_installments'] ?? '0') === '1';

        DB::beginTransaction();
        try {
            // ─── CAMPOS SIEMPRE EDITABLES ──────────────────────────────
            $data = [];

            // Notas
            $data['notes'] = trim($_POST['notes'] ?? '') ?: null;

            // Mora
            $data['late_fee_rate'] = isset($_POST['late_fee_rate'])
                ? round((float)$_POST['late_fee_rate'] / 100, 6)
                : $loan['late_fee_rate'];

            // Días de gracia
            $data['grace_days'] = max(0, min(30, (int)($_POST['grace_days'] ?? 0)));

            // Asesor
            $assigned = $_POST['assigned_to'] ?? '';
            if ($assigned === '' || !is_numeric($assigned) || (int)$assigned <= 0) {
                $data['assigned_to'] = null;
            } else {
                $exists = DB::row("SELECT id FROM users WHERE id = ? LIMIT 1", [(int)$assigned]);
                $data['assigned_to'] = $exists ? (int)$assigned : null;
            }

            // Estado
            $allowedStatus = ['active', 'paid', 'defaulted', 'cancelled', 'restructured'];
            $newStatus     = $_POST['status'] ?? $loan['status'];
            if (!in_array($newStatus, $allowedStatus, true)) {
                throw new \InvalidArgumentException('Estado inválido.');
            }
            $data['status'] = $newStatus;

            // Prioridad de pago
            $applyTo = $_POST['apply_payment_to'] ?? $loan['apply_payment_to'];
            if (in_array($applyTo, ['interest_first', 'capital'], true)) {
                $data['apply_payment_to'] = $applyTo;
            }

            // ─── RECÁLCULO DE CUOTAS (solo si se solicitó) ─────────────
            if ($recalculate) {
                $interestRate = (float)($_POST['interest_rate'] ?? 0);
                $termMonths   = (int)($_POST['term_months'] ?? 0);
                $frequency    = $_POST['payment_frequency'] ?? 'monthly';
                $rateType     = $_POST['rate_type'] ?? 'monthly';
                $firstPayDate = $_POST['first_payment_date'] ?? '';
                $disbDate     = $_POST['disbursement_date'] ?? $loan['disbursement_date'];

                if ($interestRate <= 0) {
                    throw new \InvalidArgumentException('La tasa de interés debe ser mayor a 0.');
                }
                if ($loan['loan_type'] !== 'B' && $termMonths <= 0) {
                    throw new \InvalidArgumentException('El número de cuotas debe ser mayor a 0.');
                }
                if (!$firstPayDate) {
                    throw new \InvalidArgumentException('La fecha del primer pago es requerida.');
                }

                // Verificar que no haya pagos registrados
                $payCount = (int)(DB::row(
                    "SELECT COUNT(*) as n FROM payments WHERE loan_id = ? AND voided = 0",
                    [(int)$id]
                )['n'] ?? 0);

                if ($payCount > 0) {
                    throw new \RuntimeException(
                        "No se pueden recalcular las cuotas: el préstamo tiene {$payCount} pago(s) registrado(s). "
                            . "Anule los pagos primero."
                    );
                }

                // Calcular tasa en decimal por período
                $rate = $rateType === 'annual'
                    ? $interestRate / 100 / 12
                    : $interestRate / 100;

                // Datos financieros actualizados
                $data['interest_rate']      = $rate;
                $data['rate_type']          = $rateType;
                $data['term_months']        = $loan['loan_type'] !== 'B' ? $termMonths : null;
                $data['payment_frequency']  = $frequency;
                $data['first_payment_date'] = $firstPayDate;
                $data['disbursement_date']  = $disbDate;
                $data['balance']            = (float)$loan['principal']; // resetear al principal

                // Generar nueva tabla de amortización
                $calculator = CalculatorFactory::make($loan['loan_type']);
                $loanForCalc = array_merge($loan, [
                    'interest_rate'      => $rate,
                    'rate_type'          => $rateType,
                    'term_months'        => $termMonths,
                    'payment_frequency'  => $frequency,
                    'first_payment_date' => $firstPayDate,
                ]);
                $schedule = $calculator->buildSchedule($loanForCalc);

                // Borrar cuotas anteriores y recrear
                DB::delete('loan_installments', 'loan_id = ?', [(int)$id]);

                $validInstCols = [
                    'installment_number',
                    'due_date',
                    'principal_amount',
                    'interest_amount',
                    'total_amount',
                    'balance_after',
                    'paid_amount',
                    'paid_principal',
                    'paid_interest',
                    'paid_late_fee',
                    'late_fee',
                    'days_late',
                    'status',
                ];
                foreach ($schedule['installments'] as $inst) {
                    $row = array_intersect_key($inst, array_flip($validInstCols));
                    $row['loan_id'] = (int)$id;
                    DB::insert('loan_installments', $row);
                }

                // Actualizar fecha de vencimiento
                if (!empty($schedule['installments'])) {
                    $last = end($schedule['installments']);
                    $data['maturity_date'] = $last['due_date'];
                }

                // Evento de recálculo
                DB::insert('loan_events', [
                    'loan_id'     => (int)$id,
                    'user_id'     => Auth::id(),
                    'event_type'  => 'updated',
                    'description' => 'Cuotas recalculadas por ' . Auth::user()['name'],
                    'meta'        => json_encode([
                        'new_rate'       => $interestRate,
                        'new_rate_type'  => $rateType,
                        'new_term'       => $termMonths,
                        'new_frequency'  => $frequency,
                        'total_interest' => $schedule['total_interest'],
                        'total_payment'  => $schedule['total_payment'],
                    ]),
                ]);
            }

            // ─── GUARDAR PRÉSTAMO ──────────────────────────────────────
            DB::update('loans', $data, 'id = ?', [(int)$id]);

            // Auditoría
            DB::insert('audit_log', [
                'user_id'    => Auth::id(),
                'action'     => 'update',
                'entity'     => 'loans',
                'entity_id'  => (int)$id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Evento general de edición
            DB::insert('loan_events', [
                'loan_id'     => (int)$id,
                'user_id'     => Auth::id(),
                'event_type'  => 'updated',
                'description' => 'Préstamo editado por ' . Auth::user()['name'],
                'meta'        => json_encode([
                    'fields'      => array_keys($data),
                    'recalculated' => $recalculate,
                ]),
            ]);

            DB::commit();

            $msg = $recalculate
                ? 'Préstamo actualizado y cuotas recalculadas correctamente.'
                : 'Préstamo actualizado correctamente.';
            $this->flashRedirect("/loans/$id", 'success', $msg);
        } catch (\Throwable $e) {
            DB::rollback();
            error_log('[LoanController::update] ' . $e->getMessage());
            View::flash('error', $e->getMessage());
            $this->redirect("/loans/$id/edit");
        }
    }

    /**
     * REFINANCIAR PRÉSTAMO — Solo Admin/SuperAdmin
     * Marca el préstamo actual como "restructured" y crea uno nuevo con el saldo
     */
    public function refinance(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $loan = Loan::find((int)$id);
        if (!$loan) $this->flashRedirect('/loans', 'error', 'Préstamo no encontrado.');

        if ($loan['status'] !== 'active') {
            $this->flashRedirect("/loans/$id", 'error', 'Solo se pueden refinanciar préstamos activos.');
        }

        $newPrincipal = (float)($_POST['refinance_amount']        ?? $loan['balance']);
        $newRateRaw   = (float)($_POST['refinance_rate']          ?? ($loan['interest_rate'] * 100));
        $newTerm      = (int)  ($_POST['refinance_term']          ?? ($loan['term_months'] ?? 12));
        $newFreq      = $_POST['refinance_frequency']             ?? $loan['payment_frequency'];
        $newFirstPay  = $_POST['refinance_first_payment']         ?? date('Y-m-d', strtotime('+1 month'));
        $rateType     = $_POST['refinance_rate_type']             ?? $loan['rate_type'];

        if ($newPrincipal <= 0 || $newRateRaw <= 0 || $newTerm <= 0) {
            $this->flashRedirect(
                "/loans/$id/edit",
                'error',
                'Monto, tasa y número de cuotas son obligatorios para refinanciar.'
            );
        }

        DB::beginTransaction();
        try {
            // 1. Marcar préstamo actual como restructurado
            DB::update('loans', ['status' => 'restructured'], 'id = ?', [(int)$id]);
            DB::insert('loan_events', [
                'loan_id'     => (int)$id,
                'user_id'     => Auth::id(),
                'event_type'  => 'status_change',
                'description' => 'Préstamo restructurado/refinanciado por ' . Auth::user()['name'],
                'meta'        => json_encode([
                    'previous_status'  => 'active',
                    'new_loan_amount'  => $newPrincipal,
                ]),
            ]);

            // 2. Generar número del nuevo préstamo
            $prefix  = setting('loan_number_prefix', 'PRES-');
            $last    = DB::row(
                "SELECT MAX(CAST(SUBSTRING(loan_number, ?) AS UNSIGNED)) as n
                 FROM loans WHERE loan_number LIKE ?",
                [strlen($prefix) + 1, $prefix . '%']
            );
            $loanNum = $prefix . str_pad((string)(($last['n'] ?? 0) + 1), 6, '0', STR_PAD_LEFT);

            // 3. Convertir tasa a decimal mensual
            $rate = $rateType === 'annual'
                ? $newRateRaw / 100 / 12
                : $newRateRaw / 100;

            // 4. Crear nuevo préstamo
            $newLoanData = [
                'client_id'          => $loan['client_id'],
                'aval_id'            => $loan['aval_id'] ?? null,
                'assigned_to'        => $loan['assigned_to'] ?? null,
                'created_by'         => Auth::id(),
                'loan_number'        => $loanNum,
                'loan_type'          => $loan['loan_type'],
                'principal'          => $newPrincipal,
                'interest_rate'      => $rate,
                'rate_type'          => $rateType,
                'term_months'        => $newTerm,
                'payment_frequency'  => $newFreq,
                'late_fee_rate'      => $loan['late_fee_rate'],
                'grace_days'         => $loan['grace_days'],
                'disbursement_date'  => date('Y-m-d'),
                'first_payment_date' => $newFirstPay,
                'status'             => 'active',
                'balance'            => $newPrincipal,
                'apply_payment_to'   => $loan['apply_payment_to'],
                'notes'              => 'Refinanciamiento del préstamo ' . $loan['loan_number'],
                'payment_method'     => $loan['payment_method'],
                'payment_method_cash'     => $loan['payment_method_cash']     ?? 1,
                'payment_method_transfer' => $loan['payment_method_transfer'] ?? 0,
                'payment_method_check'    => $loan['payment_method_check']    ?? 0,
                'payment_method_atm'      => $loan['payment_method_atm']      ?? 0,
            ];

            $newLoanId = DB::insert('loans', $newLoanData);

            // 5. Generar tabla de amortización del nuevo préstamo
            $calculator = CalculatorFactory::make($loan['loan_type']);
            $schedule   = $calculator->buildSchedule(array_merge($newLoanData, [
                'interest_rate' => $rate,
            ]));

            $validInstCols = [
                'installment_number',
                'due_date',
                'principal_amount',
                'interest_amount',
                'total_amount',
                'balance_after',
                'paid_amount',
                'paid_principal',
                'paid_interest',
                'paid_late_fee',
                'late_fee',
                'days_late',
                'status',
            ];
            foreach ($schedule['installments'] as $inst) {
                $row = array_intersect_key($inst, array_flip($validInstCols));
                $row['loan_id'] = $newLoanId;
                DB::insert('loan_installments', $row);
            }

            // Actualizar fecha de vencimiento del nuevo préstamo
            if (!empty($schedule['installments'])) {
                $lastInst = end($schedule['installments']);
                DB::update('loans', ['maturity_date' => $lastInst['due_date']], 'id = ?', [$newLoanId]);
            }

            // 6. Evento del nuevo préstamo
            DB::insert('loan_events', [
                'loan_id'     => $newLoanId,
                'user_id'     => Auth::id(),
                'event_type'  => 'created',
                'description' => 'Préstamo creado por refinanciamiento de ' . $loan['loan_number'],
                'meta'        => json_encode([
                    'original_loan_id' => (int)$id,
                    'schedule_summary' => [
                        'total_interest' => $schedule['total_interest'],
                        'total_payment'  => $schedule['total_payment'],
                        'installments'   => count($schedule['installments']),
                    ],
                ]),
            ]);

            // 7. Auditoría
            DB::insert('audit_log', [
                'user_id'    => Auth::id(),
                'action'     => 'create',
                'entity'     => 'loans',
                'entity_id'  => $newLoanId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            DB::commit();
            $this->flashRedirect(
                "/loans/$newLoanId",
                'success',
                "Refinanciamiento completado. Nuevo préstamo: {$loanNum}"
            );
        } catch (\Throwable $e) {
            DB::rollback();
            error_log('[LoanController::refinance] ' . $e->getMessage());
            View::flash('error', 'Error al refinanciar: ' . $e->getMessage());
            $this->redirect("/loans/$id/edit");
        }
    }

    /**
     * ELIMINAR / CANCELAR PRÉSTAMO — Solo Admin/SuperAdmin
     */
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
            $hasPayments = DB::row(
                "SELECT COUNT(*) as n FROM payments WHERE loan_id = ? AND voided = 0",
                [(int)$id]
            );
            if ((int)($hasPayments['n'] ?? 0) > 0) {
                $this->flashRedirect(
                    '/loans',
                    'error',
                    'No se puede eliminar un préstamo con pagos registrados. Cancélelo primero.'
                );
            }
            DB::delete('loan_installments', 'loan_id = ?', [(int)$id]);
            DB::delete('loan_events',       'loan_id = ?', [(int)$id]);
            DB::delete('loans',             'id = ?',      [(int)$id]);
            $this->flashRedirect('/loans', 'success', 'Préstamo ' . $loan['loan_number'] . ' eliminado.');
        }
    }

    /**
     * TABLA DE AMORTIZACIÓN (sin layout, imprimible)
     */
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

    /**
     * PAGARÉ (documento imprimible)
     */
    public function pagare(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $client       = Client::find((int)$loan['client_id']);
        $aval         = !empty($loan['aval_id']) ? Client::findAvalById((int)$loan['aval_id']) : null;
        $installments = Loan::getInstallments((int)$id);

        $this->render('documents/pagare', [
            'title'        => 'Pagaré · ' . $loan['loan_number'],
            'loan'         => $loan,
            'client'       => $client,
            'aval'         => $aval,
            'installments' => $installments,
        ], null);
    }

    /**
     * CONTRATO DE PRÉSTAMO (documento imprimible)
     */
    public function contrato(string $id): void
    {
        $loan = Loan::find((int)$id);
        if (!$loan) $this->redirect('/loans');

        $client       = Client::find((int)$loan['client_id']);
        $aval         = !empty($loan['aval_id']) ? Client::findAvalById((int)$loan['aval_id']) : null;
        $guarantee    = DB::row("SELECT * FROM loan_guarantees WHERE loan_id = ? LIMIT 1", [(int)$id]);
        $installments = Loan::getInstallments((int)$id);

        $this->render('documents/contrato', [
            'title'        => 'Contrato · ' . $loan['loan_number'],
            'loan'         => $loan,
            'client'       => $client,
            'aval'         => $aval,
            'guarantee'    => $guarantee,
            'installments' => $installments,
        ], null);
    }
}
