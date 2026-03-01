<?php
// app/services/LoanCalculator/LevelPaymentCalculator.php
// Tipo A: Cuota Nivelada (French Amortization)

namespace App\Services\LoanCalculator;

class LevelPaymentCalculator implements LoanCalculatorInterface
{
    private const FREQ_DAYS = [
        'weekly'     => 7,   'biweekly'   => 15,  'monthly'    => 30,
        'bimonthly'  => 60,  'quarterly'  => 90,  'semiannual' => 180, 'annual' => 365,
    ];

    private const FREQ_MODIFIER = [
        'weekly'     => '+7 days',   'biweekly'   => '+15 days',
        'monthly'    => '+1 month',  'bimonthly'  => '+2 months',
        'quarterly'  => '+3 months', 'semiannual' => '+6 months', 'annual' => '+1 year',
    ];

    public function buildSchedule(array $loanData): array
    {
        $principal        = (float)$loanData['principal'];
        $termPeriods      = (int)$loanData['term_months'];
        $rateType         = $loanData['rate_type'] ?? 'monthly';
        $annualRate       = (float)$loanData['interest_rate'];
        $monthlyRate      = $rateType === 'annual' ? $annualRate / 12 : $annualRate;
        $frequency        = $loanData['payment_frequency'] ?? 'monthly';
        $freqDays         = self::FREQ_DAYS[$frequency] ?? 30;
        $modifier         = self::FREQ_MODIFIER[$frequency] ?? '+1 month';
        $firstPaymentDate = new \DateTime($loanData['first_payment_date']);

        if ($termPeriods <= 0 || $principal <= 0) {
            throw new \InvalidArgumentException('Monto y número de cuotas deben ser positivos.');
        }

        $periodRate = $monthlyRate * ($freqDays / 30);

        if ($periodRate == 0) {
            $payment = $principal / $termPeriods;
        } else {
            $payment = $principal * ($periodRate * pow(1 + $periodRate, $termPeriods))
                     / (pow(1 + $periodRate, $termPeriods) - 1);
        }
        $payment = round($payment, 2);

        $balance = $principal; $installments = []; $totalInterest = 0; $totalPayment = 0;
        $dueDate = clone $firstPaymentDate;

        for ($i = 1; $i <= $termPeriods; $i++) {
            $interest = round($balance * $periodRate, 2);
            $capital  = $i === $termPeriods ? round($balance, 2) : round($payment - $interest, 2);
            $capital  = min($capital, $balance);
            $totalPay = round($capital + $interest, 2);
            $balance  = round($balance - $capital, 2);

            $installments[] = [
                'installment_number' => $i,
                'due_date'           => $dueDate->format('Y-m-d'),
                'principal_amount'   => $capital,
                'interest_amount'    => $interest,
                'total_amount'       => $totalPay,
                'balance_after'      => max(0.0, $balance),
                'paid_amount'        => 0.00, 'paid_principal' => 0.00,
                'paid_interest'      => 0.00, 'paid_late_fee'  => 0.00,
                'late_fee'           => 0.00, 'days_late'      => 0,
                'status'             => 'pending',
            ];

            $totalInterest += $interest;
            $totalPayment  += $totalPay;
            $dueDate->modify($modifier);
        }

        return [
            'installments'    => $installments,
            'monthly_payment' => $payment,
            'total_interest'  => round($totalInterest, 2),
            'total_payment'   => round($totalPayment, 2),
            'monthly_rate'    => $monthlyRate,
            'period_rate'     => $periodRate,
            'annual_rate'     => $rateType === 'annual' ? $annualRate : $monthlyRate * 12,
            'frequency'       => $frequency,
        ];
    }

    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array
    {
        $daysLate = 0; $lateFee = 0;
        $graceDays = (int)setting('grace_days', 3);

        $overdueInstallments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND due_date < ? AND status IN ('pending','partial') ORDER BY due_date",
            [$loan['id'], $asOf->format('Y-m-d')]
        );

        foreach ($overdueInstallments as $inst) {
            $daysDiff = (int)(new \DateTime($inst['due_date']))->diff($asOf)->days;
            if ($daysDiff > $graceDays) {
                $effectiveDays = $daysDiff - $graceDays;
                $pending       = $inst['total_amount'] - $inst['paid_amount'];
                $lateFee      += $pending * ((float)$loan['late_fee_rate'] / 30) * $effectiveDays;
                $daysLate      = max($daysLate, $daysDiff);
            }
        }

        return ['days_late' => $daysLate, 'late_fee' => round($lateFee, 2), 'overdue_count' => count($overdueInstallments)];
    }

    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array
    {
        $remaining = $amount; $items = [];
        $applyRule = $loan['apply_payment_to'] ?? 'interest_first';

        $installments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND status IN ('pending','partial') ORDER BY due_date ASC",
            [$loan['id']]
        );

        foreach ($installments as $inst) {
            if ($remaining <= 0) break;

            $pendingInt = round($inst['interest_amount']  - $inst['paid_interest'],  2);
            $pendingCap = round($inst['principal_amount'] - $inst['paid_principal'], 2);

            $graceDays = (int)setting('grace_days', 3);
            $daysDiff  = (int)(new \DateTime($inst['due_date']))->diff($paymentDate)->days;
            $lateFee   = 0;

            if ($paymentDate > new \DateTime($inst['due_date']) && $daysDiff > $graceDays) {
                $effectiveDays = $daysDiff - $graceDays;
                $pending       = round($inst['total_amount'] - $inst['paid_amount'], 2);
                $lateFee       = round($pending * ((float)$loan['late_fee_rate'] / 30) * $effectiveDays, 2);
            }

            $paidLateFee = 0; $paidInt = 0; $paidCap = 0;

            if ($lateFee > 0) { $paidLateFee = min($remaining, $lateFee); $remaining -= $paidLateFee; }

            if ($applyRule === 'interest_first') {
                $paidInt = min($remaining, $pendingInt); $remaining -= $paidInt;
                $paidCap = min($remaining, $pendingCap); $remaining -= $paidCap;
            } else {
                $paidCap = min($remaining, $pendingCap); $remaining -= $paidCap;
                $paidInt = min($remaining, $pendingInt); $remaining -= $paidInt;
            }

            $totalApplied = $paidCap + $paidInt + $paidLateFee;
            if ($totalApplied <= 0) continue;

            $newPaid = $inst['paid_amount'] + $paidInt + $paidCap;
            $status  = ($newPaid >= $inst['total_amount'] - 0.01) ? 'paid' : 'partial';

            $items[] = [
                'installment_id' => $inst['id'],
                'paid_capital'   => $paidCap,   'paid_interest' => $paidInt,
                'paid_late_fee'  => $paidLateFee, 'total_applied' => $totalApplied,
                'new_status'     => $status,     'days_late'     => $daysDiff,
                'late_fee'       => $lateFee,
            ];
        }

        return ['items' => $items, 'remainder' => round($remaining, 2)];
    }
}
