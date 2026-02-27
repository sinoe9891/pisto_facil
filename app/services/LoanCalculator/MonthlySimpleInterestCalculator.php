<?php
// app/services/LoanCalculator/MonthlySimpleInterestCalculator.php
// Tipo C: Interés Simple Mensual
// Regla: Interest = balance * monthly_rate each month.
// Client can pay interest only; partial capital reduces balance for next month.
// Missed month: balance accumulates (interest is added to balance or tracked separately).

namespace App\Services\LoanCalculator;

class MonthlySimpleInterestCalculator implements LoanCalculatorInterface
{
    /**
     * Build a projected schedule (informational - actual balance tracked via payments)
     * Shows what happens if client pays only interest every month for N months
     */
    public function buildSchedule(array $loanData): array
    {
        $principal        = (float)$loanData['principal'];
        $monthlyRate      = (float)$loanData['interest_rate'];
        $termMonths       = (int)($loanData['term_months'] ?? 12);
        $firstPaymentDate = new \DateTime($loanData['first_payment_date']);

        $balance      = $principal;
        $installments = [];
        $dueDate      = clone $firstPaymentDate;

        for ($i = 1; $i <= $termMonths; $i++) {
            $interest = round($balance * $monthlyRate, 2);
            $isLast   = ($i === $termMonths);

            // Interest-only payment (except last month where capital is due too)
            $totalMin  = $interest;
            $totalFull = $isLast ? ($interest + $balance) : $interest;

            $installments[] = [
                'installment_number' => $i,
                'due_date'           => $dueDate->format('Y-m-d'),
                'principal_amount'   => $isLast ? $balance : 0.00,
                'interest_amount'    => $interest,
                'total_amount'       => $totalFull,
                'minimum_payment'    => $totalMin,
                'balance_after'      => $isLast ? 0.00 : $balance,
                'paid_amount'        => 0.00,
                'paid_principal'     => 0.00,
                'paid_interest'      => 0.00,
                'paid_late_fee'      => 0.00,
                'late_fee'           => 0.00,
                'days_late'          => 0,
                'status'             => 'pending',
            ];

            $dueDate->modify('+1 month');
        }

        return [
            'installments'    => $installments,
            'monthly_payment' => round($principal * $monthlyRate, 2),
            'total_interest'  => round($principal * $monthlyRate * $termMonths, 2),
            'total_payment'   => round($principal + $principal * $monthlyRate * $termMonths, 2),
            'monthly_rate'    => $monthlyRate,
            'annual_rate'     => $monthlyRate * 12,
            'note'            => 'Proyección asumiendo saldo constante (solo intereses)',
        ];
    }

    /**
     * Calculate current month's interest + any late fees on current balance
     */
    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array
    {
        $balance    = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $graceDays  = (int)setting('grace_days', 3);

        $monthlyInterest = round($balance * $monthlyRate, 2);

        // Find overdue installments
        $overdueInstallments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND due_date < ? AND status IN ('pending','partial') ORDER BY due_date",
            [$loan['id'], $asOf->format('Y-m-d')]
        );

        $totalLateFee = 0;
        $maxDaysLate  = 0;

        foreach ($overdueInstallments as $inst) {
            $daysDiff = (int)(new \DateTime($inst['due_date']))->diff($asOf)->days;
            if ($daysDiff > $graceDays) {
                $effectiveDays = $daysDiff - $graceDays;
                $pending       = (float)$inst['interest_amount'] - (float)$inst['paid_interest'];
                $dailyLateFee  = (float)$loan['late_fee_rate'] / 30;
                $totalLateFee += round($pending * $dailyLateFee * $effectiveDays, 2);
                $maxDaysLate   = max($maxDaysLate, $daysDiff);
            }
        }

        return [
            'monthly_interest'   => $monthlyInterest,
            'late_fee'           => round($totalLateFee, 2),
            'total_due'          => round($monthlyInterest + $totalLateFee, 2),
            'days_late'          => $maxDaysLate,
            'balance'            => $balance,
            'balance_plus_interest' => round($balance + $monthlyInterest, 2),
        ];
    }

    /**
     * Apply a payment to Type C loan
     * Priority: late_fee → interest → capital
     */
    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array
    {
        $remaining   = $amount;
        $balance     = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $graceDays   = (int)setting('grace_days', 3);
        $items       = [];

        // Get pending installments
        $installments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND status IN ('pending','partial') ORDER BY due_date ASC",
            [$loan['id']]
        );

        foreach ($installments as $inst) {
            if ($remaining <= 0) break;

            $daysDiff = (int)(new \DateTime($inst['due_date']))->diff($paymentDate)->days;
            $isOverdue = $paymentDate > new \DateTime($inst['due_date']);

            // Late fee on overdue interest
            $lateFee = 0;
            if ($isOverdue && $daysDiff > $graceDays) {
                $effectiveDays  = $daysDiff - $graceDays;
                $pendingInterest = (float)$inst['interest_amount'] - (float)$inst['paid_interest'];
                $lateFee        = round($pendingInterest * ((float)$loan['late_fee_rate'] / 30) * $effectiveDays, 2);
            }

            $pendingInt = round((float)$inst['interest_amount'] - (float)$inst['paid_interest'], 2);
            $pendingCap = round((float)$inst['principal_amount'] - (float)$inst['paid_principal'], 2);

            $paidLateFee = 0;
            $paidInt     = 0;
            $paidCap     = 0;

            if ($lateFee > 0) {
                $paidLateFee = min($remaining, $lateFee);
                $remaining  -= $paidLateFee;
            }
            if ($pendingInt > 0) {
                $paidInt    = min($remaining, $pendingInt);
                $remaining -= $paidInt;
            }
            if ($pendingCap > 0) {
                $paidCap    = min($remaining, $pendingCap);
                $remaining -= $paidCap;
                $balance   -= $paidCap;
            }

            $totalApplied = $paidCap + $paidInt + $paidLateFee;
            if ($totalApplied <= 0) continue;

            $newPaidMain  = (float)$inst['paid_amount'] + $paidInt + $paidCap;
            $status       = $paidCap >= $pendingCap && $paidInt >= $pendingInt ? 'paid' : 'partial';

            $items[] = [
                'installment_id' => $inst['id'],
                'paid_capital'   => $paidCap,
                'paid_interest'  => $paidInt,
                'paid_late_fee'  => $paidLateFee,
                'total_applied'  => $totalApplied,
                'new_status'     => $status,
                'days_late'      => $daysDiff,
                'late_fee'       => $lateFee,
            ];
        }

        // Any excess goes to capital (if configured)
        if ($remaining > 0 && $balance > 0) {
            $paidCap   = min($remaining, $balance);
            $remaining -= $paidCap;
            $balance   -= $paidCap;
            $items[] = [
                'installment_id' => null,
                'paid_capital'   => $paidCap,
                'paid_interest'  => 0,
                'paid_late_fee'  => 0,
                'total_applied'  => $paidCap,
                'new_status'     => 'extra_capital',
                'days_late'      => 0,
                'late_fee'       => 0,
            ];
        }

        return [
            'items'       => $items,
            'new_balance' => round(max(0, $balance), 2),
            'remainder'   => round($remaining, 2),
        ];
    }
}