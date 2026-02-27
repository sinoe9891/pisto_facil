<?php
// app/services/LoanCalculator/LevelPaymentCalculator.php
// Tipo A: Cuota Nivelada (French Amortization)

namespace App\Services\LoanCalculator;

class LevelPaymentCalculator implements LoanCalculatorInterface
{
    /**
     * Build amortization schedule
     * loanData keys: principal, interest_rate, rate_type (monthly|annual),
     *                term_months, first_payment_date, disbursement_date
     */
    public function buildSchedule(array $loanData): array
    {
        $principal         = (float)$loanData['principal'];
        $termMonths        = (int)$loanData['term_months'];
        $rateType          = $loanData['rate_type'] ?? 'monthly';
        $annualRate        = (float)$loanData['interest_rate'];
        $monthlyRate       = $rateType === 'annual' ? $annualRate / 12 : $annualRate;
        $firstPaymentDate  = new \DateTime($loanData['first_payment_date']);

        if ($termMonths <= 0 || $principal <= 0) {
            throw new \InvalidArgumentException('Monto y plazo deben ser positivos.');
        }

        // Fixed monthly payment (PMT formula)
        if ($monthlyRate == 0) {
            $payment = $principal / $termMonths;
        } else {
            $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths))
                     / (pow(1 + $monthlyRate, $termMonths) - 1);
        }
        $payment = round($payment, 2);

        $balance       = $principal;
        $installments  = [];
        $totalInterest = 0;
        $totalPayment  = 0;

        $dueDate = clone $firstPaymentDate;

        for ($i = 1; $i <= $termMonths; $i++) {
            $interest    = round($balance * $monthlyRate, 2);
            $capital     = ($i === $termMonths)
                           ? round($balance, 2)           // last installment: full remaining balance
                           : round($payment - $interest, 2);
            $capital     = min($capital, $balance);
            $totalPay    = round($capital + $interest, 2);
            $balance     = round($balance - $capital, 2);

            $installments[] = [
                'installment_number' => $i,
                'due_date'           => $dueDate->format('Y-m-d'),
                'principal_amount'   => $capital,
                'interest_amount'    => $interest,
                'total_amount'       => $totalPay,
                'balance_after'      => $balance,
                'paid_amount'        => 0.00,
                'paid_principal'     => 0.00,
                'paid_interest'      => 0.00,
                'paid_late_fee'      => 0.00,
                'late_fee'           => 0.00,
                'days_late'          => 0,
                'status'             => 'pending',
            ];

            $totalInterest += $interest;
            $totalPayment  += $totalPay;

            // Advance one month
            $dueDate->modify('+1 month');
        }

        return [
            'installments'       => $installments,
            'monthly_payment'    => $payment,
            'total_interest'     => round($totalInterest, 2),
            'total_payment'      => round($totalPayment, 2),
            'monthly_rate'       => $monthlyRate,
            'annual_rate'        => $rateType === 'annual' ? $annualRate : $monthlyRate * 12,
        ];
    }

    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array
    {
        // For Type A, interest is pre-calculated in installments
        $daysLate = 0;
        $lateFee  = 0;
        $graceDays = (int)setting('grace_days', 3);

        // Find unpaid overdue installments
        $overdueInstallments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND due_date < ? AND status IN ('pending','partial') ORDER BY due_date",
            [$loan['id'], $asOf->format('Y-m-d')]
        );

        foreach ($overdueInstallments as $inst) {
            $daysDiff = (int)(new \DateTime($inst['due_date']))->diff($asOf)->days;
            if ($daysDiff > $graceDays) {
                $effectiveDays = $daysDiff - $graceDays;
                $pending       = $inst['total_amount'] - $inst['paid_amount'];
                $dailyRate     = (float)$loan['late_fee_rate'] / 30;
                $lateFee      += $pending * $dailyRate * $effectiveDays;
                $daysLate      = max($daysLate, $daysDiff);
            }
        }

        return ['days_late' => $daysLate, 'late_fee' => round($lateFee, 2)];
    }

    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array
    {
        $remaining = $amount;
        $items     = [];
        $applyRule = $loan['apply_payment_to'] ?? 'interest_first';

        // Get oldest pending installments first
        $installments = \App\Core\DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? AND status IN ('pending','partial') ORDER BY due_date ASC",
            [$loan['id']]
        );

        foreach ($installments as $inst) {
            if ($remaining <= 0) break;

            $pending     = round($inst['total_amount'] - $inst['paid_amount'], 2);
            $pendingInt  = round($inst['interest_amount'] - $inst['paid_interest'], 2);
            $pendingCap  = round($inst['principal_amount'] - $inst['paid_principal'], 2);

            // Late fee
            $graceDays  = (int)setting('grace_days', 3);
            $daysDiff   = (int)(new \DateTime($inst['due_date']))->diff($paymentDate)->days;
            $lateFee    = 0;
            if ($paymentDate > new \DateTime($inst['due_date']) && $daysDiff > $graceDays) {
                $effectiveDays = $daysDiff - $graceDays;
                $dailyRate     = (float)$loan['late_fee_rate'] / 30;
                $lateFee       = round(($pending) * $dailyRate * $effectiveDays, 2);
            }

            // Apply to late fee first, then interest, then capital (or capital first)
            $paidLateFee = 0;
            $paidInt     = 0;
            $paidCap     = 0;

            if ($lateFee > 0) {
                $paidLateFee = min($remaining, $lateFee);
                $remaining  -= $paidLateFee;
            }

            if ($applyRule === 'interest_first') {
                $paidInt    = min($remaining, $pendingInt);
                $remaining -= $paidInt;
                $paidCap    = min($remaining, $pendingCap);
                $remaining -= $paidCap;
            } else {
                $paidCap    = min($remaining, $pendingCap);
                $remaining -= $paidCap;
                $paidInt    = min($remaining, $pendingInt);
                $remaining -= $paidInt;
            }

            $totalApplied = $paidCap + $paidInt + $paidLateFee;
            if ($totalApplied <= 0) continue;

            // Determine new status
            $newPaidAmount = $inst['paid_amount'] + $totalApplied - $paidLateFee;
            $status = ($newPaidAmount >= $inst['total_amount'] - 0.01) ? 'paid' : 'partial';

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

        return [
            'items'     => $items,
            'remainder' => round($remaining, 2),  // unallocated excess
        ];
    }
}