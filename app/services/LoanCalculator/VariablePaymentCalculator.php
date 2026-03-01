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
    private const FREQ_DAYS = [
        'weekly' => 7, 'biweekly' => 15, 'monthly' => 30,
        'bimonthly' => 60, 'quarterly' => 90, 'semiannual' => 180, 'annual' => 365,
    ];

    private const FREQ_MODIFIER = [
        'weekly' => '+7 days', 'biweekly' => '+15 days', 'monthly' => '+1 month',
        'bimonthly' => '+2 months', 'quarterly' => '+3 months',
        'semiannual' => '+6 months', 'annual' => '+1 year',
    ];

    /**
     * Tipo B: Crea cuotas proyectadas de SOLO INTERÉS por período.
     * El capital no amortiza en el calendario — se abona libremente al pagar.
     * Si no se especifica term_months, crea 1 cuota abierta (comportamiento legacy).
     */
    public function buildSchedule(array $loanData): array
    {
        $principal        = (float)$loanData['principal'];
        $monthlyRate      = (float)$loanData['interest_rate'];
        $termPeriods      = isset($loanData['term_months']) && $loanData['term_months'] > 0
                            ? (int)$loanData['term_months'] : 0;
        $frequency        = $loanData['payment_frequency'] ?? 'monthly';
        $freqDays         = self::FREQ_DAYS[$frequency] ?? 30;
        $modifier         = self::FREQ_MODIFIER[$frequency] ?? '+1 month';
        $firstPaymentDate = new \DateTime($loanData['first_payment_date']);

        // Sin plazo definido → 1 cuota abierta (comportamiento original)
        if ($termPeriods <= 0) {
            return [
                'installments' => [[
                    'installment_number' => 1,
                    'due_date'           => $firstPaymentDate->format('Y-m-d'),
                    'principal_amount'   => $principal,
                    'interest_amount'    => 0.00,
                    'total_amount'       => $principal,
                    'balance_after'      => 0.00,
                    'paid_amount'        => 0.00, 'paid_principal' => 0.00,
                    'paid_interest'      => 0.00, 'paid_late_fee'  => 0.00,
                    'late_fee'           => 0.00, 'days_late'      => 0,
                    'status'             => 'pending',
                ]],
                'monthly_payment' => 0, 'total_interest' => 0,
                'total_payment'   => $principal,
                'monthly_rate'    => $monthlyRate, 'annual_rate' => $monthlyRate * 12,
                'note'            => 'Tipo B sin plazo: cuota abierta. Interés calculado al pagar.',
            ];
        }

        // Con plazo → cuotas con CAPITAL FIJO por período, interés sobre saldo decreciente
        // La cuota VARÍA (decrece) porque el interés baja a medida que baja el saldo
        $periodRate      = $monthlyRate * ($freqDays / 30);
        $capitalPerPeriod = round($principal / $termPeriods, 2);
        $balance         = $principal;
        $installments    = [];
        $totalInterest   = 0;
        $dueDate         = clone $firstPaymentDate;

        for ($i = 1; $i <= $termPeriods; $i++) {
            $interest = round($balance * $periodRate, 2);
            $isLast   = ($i === $termPeriods);
            // Capital fijo, excepto último período que cancela el residuo exacto
            $capital  = $isLast ? $balance : $capitalPerPeriod;
            $total    = round($capital + $interest, 2);
            $balance  = round($balance - $capital, 2);

            $installments[] = [
                'installment_number' => $i,
                'due_date'           => $dueDate->format('Y-m-d'),
                'principal_amount'   => $capital,
                'interest_amount'    => $interest,
                'total_amount'       => $total,
                'balance_after'      => max(0.00, $balance),
                'paid_amount'        => 0.00, 'paid_principal' => 0.00,
                'paid_interest'      => 0.00, 'paid_late_fee'  => 0.00,
                'late_fee'           => 0.00, 'days_late'      => 0,
                'status'             => 'pending',
            ];

            $totalInterest += $interest;
            $dueDate->modify($modifier);
        }

        // Primera cuota (la más alta) como referencia
        $firstPayment = round($capitalPerPeriod + round($principal * $periodRate, 2), 2);

        return [
            'installments'    => $installments,
            'monthly_payment' => $firstPayment,   // cuota inicial (máxima)
            'total_interest'  => round($totalInterest, 2),
            'total_payment'   => round($principal + $totalInterest, 2),
            'monthly_rate'    => $monthlyRate,
            'period_rate'     => $periodRate,
            'annual_rate'     => $monthlyRate * 12,
            'frequency'       => $frequency,
            'note'            => 'Tipo B: capital fijo por período, interés sobre saldo decreciente. Cuota varía (decrece).',
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