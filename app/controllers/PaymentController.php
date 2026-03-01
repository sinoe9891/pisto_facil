<?php
// app/controllers/PaymentController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\Loan;
use App\Services\LoanCalculator\CalculatorFactory;

class PaymentController extends Controller
{
    // LIST
    public function index(): void
    {
        $search = $this->get('search', '');
        $from   = $this->get('from', date('Y-m-01'));
        $to     = $this->get('to', date('Y-m-d'));

        $sql    = "SELECT p.id, p.payment_number, p.payment_date, p.total_received,
                          p.payment_method, p.receipt_number, p.voided,
                          l.loan_number, l.id as loan_id,
                          CONCAT(c.first_name,' ',c.last_name) as client_name,
                          u.name as registered_by_name
                   FROM payments p
                   JOIN loans l    ON l.id  = p.loan_id
                   JOIN clients c  ON c.id  = l.client_id
                   JOIN users u    ON u.id  = p.registered_by
                   WHERE p.payment_date BETWEEN ? AND ?";
        $params = [$from, $to];

        if ($search) {
            $sql   .= " AND (l.loan_number LIKE ? OR CONCAT(c.first_name,' ',c.last_name) LIKE ? OR p.payment_number LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if (Auth::role() === 'asesor') {
            $sql .= " AND l.assigned_to = ?"; $params[] = Auth::id();
        }
        $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

        $payments = DB::all($sql, $params);
        $totalAmt = array_sum(array_map(fn($p) => $p['voided'] ? 0 : $p['total_received'], $payments));

        $this->render('payments/index', [
            'title'    => 'Pagos',
            'payments' => $payments,
            'totalAmt' => $totalAmt,
            'search'   => $search,
            'from'     => $from,
            'to'       => $to,
        ]);
    }

    // CREATE FORM
    public function create(): void
    {
        $loanId = (int)$this->get('loan_id', 0);
        $loan   = $loanId ? Loan::find($loanId) : null;

        if ($loan && $loan['status'] !== 'active') {
            $this->flashRedirect('/loans/' . $loanId, 'error', 'Este préstamo no está activo.');
        }

        $activeLoans = [];
        if (!$loan) {
            $scope = Auth::role() === 'asesor' ? " AND l.assigned_to = " . (int)Auth::id() : '';
            $activeLoans = DB::all(
                "SELECT l.id, l.loan_number, l.balance, l.loan_type,
                        CONCAT(c.first_name,' ',c.last_name) as client_name
                 FROM loans l JOIN clients c ON c.id = l.client_id
                 WHERE l.status = 'active' $scope
                 ORDER BY c.last_name"
            );
        }

        $currentState    = [];
        $nextInstallment = null;
        if ($loan) {
            $calculator      = CalculatorFactory::make($loan['loan_type']);
            $currentState    = $calculator->calculateCurrentInterest($loan, new \DateTime());
            $nextInstallment = DB::row(
                "SELECT * FROM loan_installments WHERE loan_id = ? AND status IN ('pending','partial') ORDER BY due_date LIMIT 1",
                [$loan['id']]
            );
        }

        $this->render('payments/create', [
            'title'           => 'Registrar Pago',
            'loan'            => $loan,
            'activeLoans'     => $activeLoans,
            'currentState'    => $currentState,
            'nextInstallment' => $nextInstallment,
        ]);
    }

    // STORE
    public function store(): void
    {
        CSRF::check();

        $loanId  = (int)$this->post('loan_id', 0);
        $amount  = (float)$this->post('amount', 0);
        $date    = $this->post('payment_date', date('Y-m-d'));
        $method  = $this->post('payment_method', 'cash');
        $receipt = trim($this->post('receipt_number', ''));
        $notes   = trim($this->post('notes', ''));

        $v = Validator::make(
            ['loan_id' => $loanId, 'amount' => $amount, 'payment_date' => $date],
            ['loan_id' => 'required|numeric|min_val:1',
             'amount'  => 'required|numeric|min_val:0.01',
             'payment_date' => 'required|date']
        );
        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/payments/create?loan_id=' . $loanId);
        }

        $loan = Loan::find($loanId);
        if (!$loan || $loan['status'] !== 'active') {
            $this->flashRedirect('/payments/create', 'error', 'Préstamo no válido o inactivo.');
        }

        $prefix = setting('payment_number_prefix', 'PAG-');
        $last   = DB::row(
            "SELECT MAX(CAST(SUBSTRING(payment_number, ?) AS UNSIGNED)) as n FROM payments WHERE payment_number LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        );
        $payNum = $prefix . str_pad((string)(($last['n'] ?? 0) + 1), 6, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $payId = (int)DB::insert('payments', [
                'loan_id'        => $loanId,
                'payment_number' => $payNum,
                'payment_date'   => $date,
                'total_received' => $amount,
                'receipt_number' => $receipt ?: null,
                'payment_method' => $method,
                'notes'          => $notes ?: null,
                'registered_by'  => Auth::id(),
            ]);

            $calculator = CalculatorFactory::make($loan['loan_type']);
            $result     = $calculator->applyPayment($loan, $amount, new \DateTime($date));

            $totalCapital  = 0;
            $totalInterest = 0;
            $totalLateFee  = 0;

            foreach ($result['items'] as $item) {
                $status = $item['new_status'] ?? '';
                $isExtraCapital  = ($status === 'extra_capital');
                $isExtraInterest = ($status === 'extra_interest');

                // Registrar partidas del pago
                if (($item['paid_capital'] ?? 0) > 0) {
                    DB::insert('payment_items', [
                        'payment_id'     => $payId,
                        'installment_id' => $item['installment_id'] ?? null,
                        'item_type'      => 'capital',
                        'amount'         => $item['paid_capital'],
                    ]);
                    $totalCapital += $item['paid_capital'];
                }
                if (($item['paid_interest'] ?? 0) > 0) {
                    DB::insert('payment_items', [
                        'payment_id'     => $payId,
                        'installment_id' => $item['installment_id'] ?? null,
                        'item_type'      => 'interest',
                        'amount'         => $item['paid_interest'],
                    ]);
                    $totalInterest += $item['paid_interest'];
                }
                if (($item['paid_late_fee'] ?? 0) > 0) {
                    DB::insert('payment_items', [
                        'payment_id'     => $payId,
                        'installment_id' => $item['installment_id'] ?? null,
                        'item_type'      => 'late_fee',
                        'amount'         => $item['paid_late_fee'],
                    ]);
                    $totalLateFee += $item['paid_late_fee'];
                }

                if (!($item['installment_id'] ?? null)) continue;

                if ($isExtraCapital) {
                    // Abono voluntario de capital: solo actualizar paid_principal
                    $inst = DB::row("SELECT * FROM loan_installments WHERE id = ?", [$item['installment_id']]);
                    if ($inst) {
                        DB::update('loan_installments', [
                            'paid_principal' => (float)$inst['paid_principal'] + $item['paid_capital'],
                            'paid_amount'    => (float)$inst['paid_amount']    + $item['paid_capital'],
                        ], 'id = ?', [$item['installment_id']]);
                    }
                } elseif ($isExtraInterest) {
                    // Interés acumulado extra (más períodos de los que hay en el DB):
                    // Registrar como paid_interest adicional en la última cuota
                    $inst = DB::row("SELECT * FROM loan_installments WHERE id = ?", [$item['installment_id']]);
                    if ($inst) {
                        DB::update('loan_installments', [
                            'paid_interest' => (float)$inst['paid_interest'] + $item['paid_interest'],
                            'paid_amount'   => (float)$inst['paid_amount']   + $item['paid_interest'],
                            'paid_date'     => $date,
                        ], 'id = ?', [$item['installment_id']]);
                    }
                } else {
                    // Cuota normal: actualizar todos los campos
                    $inst = DB::row("SELECT * FROM loan_installments WHERE id = ?", [$item['installment_id']]);
                    if ($inst) {
                        DB::update('loan_installments', [
                            'paid_amount'    => (float)$inst['paid_amount']    + ($item['paid_interest'] ?? 0) + ($item['paid_capital'] ?? 0),
                            'paid_principal' => (float)$inst['paid_principal'] + ($item['paid_capital'] ?? 0),
                            'paid_interest'  => (float)$inst['paid_interest']  + ($item['paid_interest'] ?? 0),
                            'paid_late_fee'  => (float)$inst['paid_late_fee']  + ($item['paid_late_fee'] ?? 0),
                            'status'         => $item['new_status'],
                            'paid_date'      => $date,
                            'days_late'      => $item['days_late'] ?? 0,
                            'late_fee'       => $item['late_fee']  ?? 0,
                        ], 'id = ?', [$item['installment_id']]);
                    }
                }
            }

            // Actualizar saldo del préstamo
            $newBalance = max(0, round((float)$loan['balance'] - $totalCapital, 2));
            Loan::updateBalance($loanId, $newBalance, [
                'total_paid'            => (float)$loan['total_paid']            + $amount,
                'total_interest_paid'   => (float)$loan['total_interest_paid']   + $totalInterest,
                'total_late_fees_paid'  => (float)$loan['total_late_fees_paid']  + $totalLateFee,
                'last_payment_date'     => $date,
            ]);

            Loan::markPaidIfComplete($loanId);

            DB::insert('loan_events', [
                'loan_id'    => $loanId,
                'user_id'    => Auth::id(),
                'event_type' => 'payment',
                'description'=> "Pago registrado: $payNum · $amount",
                'meta'       => json_encode(['result' => $result, 'totals' => compact('totalCapital','totalInterest','totalLateFee')]),
            ]);

            DB::commit();
            $this->flashRedirect("/payments/$payId", 'success', "Pago $payNum registrado exitosamente.");

        } catch (\Throwable $e) {
            DB::rollback();
            error_log('[PaymentController] ' . $e->getMessage());
            View::flash('error', 'Error al registrar pago: ' . $e->getMessage());
            $this->redirect('/payments/create?loan_id=' . $loanId);
        }
    }

    // SHOW
    public function show(string $id): void
    {
        $payment = DB::row(
            "SELECT p.*, l.loan_number, l.loan_type, l.id as loan_id,
                    CONCAT(c.first_name,' ',c.last_name) as client_name, c.id as client_id,
                    u.name as registered_by_name,
                    vb.name as voided_by_name
             FROM payments p
             JOIN loans l   ON l.id  = p.loan_id
             JOIN clients c ON c.id  = l.client_id
             JOIN users u   ON u.id  = p.registered_by
             LEFT JOIN users vb ON vb.id = p.voided_by
             WHERE p.id = ?",
            [(int)$id]
        );
        if (!$payment) $this->redirect('/payments');

        $items = DB::all(
            "SELECT pi.*, li.due_date, li.installment_number
             FROM payment_items pi
             LEFT JOIN loan_installments li ON li.id = pi.installment_id
             WHERE pi.payment_id = ?",
            [(int)$id]
        );

        $this->render('payments/show', [
            'title'   => 'Pago ' . $payment['payment_number'],
            'payment' => $payment,
            'items'   => $items,
        ]);
    }

    // VOID
    public function voidPayment(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        $payment = DB::row("SELECT * FROM payments WHERE id = ?", [(int)$id]);
        if (!$payment || $payment['voided']) {
            $this->flashRedirect('/payments', 'error', 'Pago no encontrado o ya anulado.');
        }

        $reason = $this->get('reason', 'Anulado por administrador');

        DB::beginTransaction();
        try {
            $items = DB::all("SELECT * FROM payment_items WHERE payment_id = ?", [(int)$id]);
            $reverseCapital  = 0;
            $reverseInterest = 0;
            $reverseLateFee  = 0;

            foreach ($items as $item) {
                if ($item['installment_id']) {
                    $inst = DB::row("SELECT * FROM loan_installments WHERE id = ?", [$item['installment_id']]);
                    if ($inst) {
                        $newPaidAmt  = max(0, (float)$inst['paid_amount']    - ($item['item_type'] !== 'late_fee' ? $item['amount'] : 0));
                        $newPaidPrin = max(0, (float)$inst['paid_principal']  - ($item['item_type'] === 'capital'   ? $item['amount'] : 0));
                        $newPaidInt  = max(0, (float)$inst['paid_interest']   - ($item['item_type'] === 'interest'  ? $item['amount'] : 0));
                        $newPaidLF   = max(0, (float)$inst['paid_late_fee']   - ($item['item_type'] === 'late_fee'  ? $item['amount'] : 0));
                        $newStatus   = $newPaidAmt > 0.01 ? 'partial' : 'pending';

                        DB::update('loan_installments', [
                            'paid_amount'    => $newPaidAmt,
                            'paid_principal' => $newPaidPrin,
                            'paid_interest'  => $newPaidInt,
                            'paid_late_fee'  => $newPaidLF,
                            'status'         => $newStatus,
                            'paid_date'      => null,
                        ], 'id = ?', [$item['installment_id']]);
                    }
                }
                match ($item['item_type']) {
                    'capital'  => $reverseCapital  += $item['amount'],
                    'interest' => $reverseInterest += $item['amount'],
                    'late_fee' => $reverseLateFee  += $item['amount'],
                    default    => null,
                };
            }

            $loan = Loan::find((int)$payment['loan_id']);
            if ($loan) {
                $restoredBalance = (float)$loan['balance'] + $reverseCapital;
                Loan::updateBalance((int)$payment['loan_id'], $restoredBalance, [
                    'total_paid'           => max(0, (float)$loan['total_paid']           - $payment['total_received']),
                    'total_interest_paid'  => max(0, (float)$loan['total_interest_paid']  - $reverseInterest),
                    'total_late_fees_paid' => max(0, (float)$loan['total_late_fees_paid'] - $reverseLateFee),
                    'status'               => 'active',
                ]);
            }

            DB::update('payments', [
                'voided'     => 1,
                'voided_by'  => Auth::id(),
                'voided_at'  => date('Y-m-d H:i:s'),
                'void_reason'=> $reason,
            ], 'id = ?', [(int)$id]);

            DB::commit();
            $this->flashRedirect('/payments/' . $id, 'success', 'Pago anulado correctamente.');
        } catch (\Throwable $e) {
            DB::rollback();
            $this->flashRedirect('/payments/' . $id, 'error', 'Error al anular: ' . $e->getMessage());
        }
    }
}