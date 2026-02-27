<?php
// app/services/LoanCalculator/VariablePaymentCalculator.php
// Tipo B: Cuota Variable / Abonos
// No fixed installments. Tracks: capital, interest corriente, interest moratorio, otros.
// Interest calculated by days (actual days elapsed since last payment or disbursement).

namespace App\Services\LoanCalculator;

class VariablePaymentCalculator implements LoanCalculatorInterface
{
    /**
     * For Type B, buildSchedule returns only a single summary "open" entry
     * since there's no fixed schedule. Returns projection for info only.
     */
    public function buildSchedule(array $loanData): array
    {
        $principal        = (float)$loanData['principal'];
        $monthlyRate      = (float)$loanData['interest_rate'];
        $firstPaymentDate = new \DateTime($loanData['first_payment_date']);

        // Create a single "revolving" installment
        $installments = [[
            'installment_number' => 1,
            'due_date'           => $firstPaymentDate->format('Y-m-d'),
            'principal_amount'   => $principal,
            'interest_amount'    => 0.00,     // Calculated on payment
            'total_amount'       => $principal,
            'balance_after'      => 0.00,
            'paid_amount'        => 0.00,
            'paid_principal'     => 0.00,
            'paid_interest'      => 0.00,
            'paid_late_fee'      => 0.00,
            'late_fee'           => 0.00,
            'days_late'          => 0,
            'status'             => 'pending',
        ]];

        return [
            'installments'    => $installments,
            'monthly_payment' => 0,
            'total_interest'  => 0,
            'total_payment'   => $principal,
            'monthly_rate'    => $monthlyRate,
            'annual_rate'     => $monthlyRate * 12,
            'note'            => 'Tipo B: Pagos variables. El interés se calcula al registrar cada pago.',
        ];
    }

    /**
     * Calculate interest by days elapsed since last payment or disbursement date
     */
    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array
    {
        $balance     = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $lateFeeRate = (float)$loan['late_fee_rate'];
        $graceDays   = (int)setting('grace_days', 3);

        // Find last payment date or disbursement date
        $lastEvent = \App\Core\DB::row(
            "SELECT MAX(payment_date) as last_date FROM payments WHERE loan_id = ? AND voided = 0",
            [$loan['id']]
        );
        $fromDate = new \DateTime($lastEvent['last_date'] ?? $loan['disbursement_date']);

        $totalDays   = (int)$fromDate->diff($asOf)->days;
        $dailyRate   = $monthlyRate / 30;

        // Accrued interest (current)
        $accruedInterest = round($balance * $dailyRate * $totalDays, 2);

        // Late fee if past due date
        $dueDate   = new \DateTime($loan['first_payment_date'] ?? $loan['disbursement_date']);
        $daysLate  = 0;
        $lateFee   = 0;

        if ($asOf > $dueDate) {
            $daysLate = max(0, (int)$dueDate->diff($asOf)->days - $graceDays);
            if ($daysLate > 0) {
                $dailyLate = $lateFeeRate / 30;
                $lateFee   = round($balance * $dailyLate * $daysLate, 2);
            }
        }

        return [
            'days_elapsed'   => $totalDays,
            'days_late'      => $daysLate,
            'accrued_interest'=> $accruedInterest,
            'late_fee'       => $lateFee,
            'total_due'      => round($balance + $accruedInterest + $lateFee, 2),
            'balance'        => $balance,
        ];
    }

    /**
     * Apply payment to Type B loan (days-based)
     */
    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array
    {
        $remaining   = $amount;
        $balance     = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $lateFeeRate = (float)$loan['late_fee_rate'];
        $graceDays   = (int)setting('grace_days', 3);

        // Calculate days since last event
        $lastEvent = \App\Core\DB::row(
            "SELECT MAX(payment_date) as last_date FROM payments WHERE loan_id = ? AND voided = 0",
            [$loan['id']]
        );
        $fromDate  = new \DateTime($lastEvent['last_date'] ?? $loan['disbursement_date']);
        $totalDays = max(0, (int)$fromDate->diff($paymentDate)->days);
        $dailyRate = $monthlyRate / 30;

        $accruedInterest = round($balance * $dailyRate * $totalDays, 2);

        // Late fee
        $lateFee = 0;
        $firstDue = new \DateTime($loan['first_payment_date'] ?? $loan['disbursement_date']);
        $daysLate = 0;
        if ($paymentDate > $firstDue) {
            $daysLate = max(0, (int)$firstDue->diff($paymentDate)->days - $graceDays);
            if ($daysLate > 0) {
                $lateFee = round($balance * ($lateFeeRate / 30) * $daysLate, 2);
            }
        }

        $items       = [];
        $paidLateFee = 0;
        $paidInt     = 0;
        $paidCap     = 0;

        // Priority: late fee → interest → capital
        if ($lateFee > 0) {
            $paidLateFee = min($remaining, $lateFee);
            $remaining  -= $paidLateFee;
        }
        $paidInt    = min($remaining, $accruedInterest);
        $remaining -= $paidInt;
        $paidCap    = min($remaining, $balance);
        $remaining -= $paidCap;
        $balance   -= $paidCap;

        // Update the single installment record
        $inst = \App\Core\DB::row(
            "SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number LIMIT 1",
            [$loan['id']]
        );

        if ($inst) {
            $items[] = [
                'installment_id' => $inst['id'],
                'paid_capital'   => $paidCap,
                'paid_interest'  => $paidInt,
                'paid_late_fee'  => $paidLateFee,
                'total_applied'  => $paidCap + $paidInt + $paidLateFee,
                'new_status'     => $balance <= 0.01 ? 'paid' : 'partial',
                'days_late'      => $daysLate,
                'late_fee'       => $lateFee,
                'days_elapsed'   => $totalDays,
                'accrued_interest' => $accruedInterest,
            ];
        }

        return [
            'items'       => $items,
            'new_balance' => round(max(0, $balance), 2),
            'remainder'   => round($remaining, 2),
        ];
    }
}