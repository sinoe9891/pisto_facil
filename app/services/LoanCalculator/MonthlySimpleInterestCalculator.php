<?php
// app/services/LoanCalculator/MonthlySimpleInterestCalculator.php
// Tipo C: Interés Simple por Período
// Cuota mínima = solo interés por período sobre el saldo (constante hasta abono voluntario).
// Si el cliente NO paga a tiempo, el interés ACUMULA por cada período transcurrido.
// El capital no amortiza en el calendario — se abona voluntariamente.

namespace App\Services\LoanCalculator;

class MonthlySimpleInterestCalculator implements LoanCalculatorInterface
{
    private const FREQ_DAYS = [
        'weekly' => 7, 'biweekly' => 15, 'monthly' => 30,
        'bimonthly' => 60, 'quarterly' => 90, 'semiannual' => 180, 'annual' => 365,
    ];

    private const FREQ_MODIFIER = [
        'weekly' => '+7 days', 'biweekly' => '+15 days', 'monthly' => '+1 month',
        'bimonthly' => '+2 months', 'quarterly' => '+3 months',
        'semiannual' => '+6 months', 'annual' => '+1 year',
    ];

    // ─── BUILD SCHEDULE ──────────────────────────────────────────────────────────
    public function buildSchedule(array $loanData): array
    {
        $principal        = (float)$loanData['principal'];
        $monthlyRate      = (float)$loanData['interest_rate'];
        $termPeriods      = (int)($loanData['term_months'] ?? 12);
        $frequency        = $loanData['payment_frequency'] ?? 'monthly';
        $freqDays         = self::FREQ_DAYS[$frequency] ?? 30;
        $modifier         = self::FREQ_MODIFIER[$frequency] ?? '+1 month';
        $firstPaymentDate = new \DateTime($loanData['first_payment_date']);

        $periodRate   = $monthlyRate * ($freqDays / 30);
        $balance      = $principal;
        $installments = [];
        $dueDate      = clone $firstPaymentDate;

        for ($i = 1; $i <= $termPeriods; $i++) {
            $interest  = round($balance * $periodRate, 2);
            $isLast    = ($i === $termPeriods);
            $capital   = $isLast ? $balance : 0.00;
            $totalFull = round($capital + $interest, 2);

            $installments[] = [
                'installment_number' => $i,
                'due_date'           => $dueDate->format('Y-m-d'),
                'principal_amount'   => $capital,
                'interest_amount'    => $interest,
                'total_amount'       => $totalFull,
                'balance_after'      => $isLast ? 0.00 : $balance,
                'paid_amount'        => 0.00, 'paid_principal' => 0.00,
                'paid_interest'      => 0.00, 'paid_late_fee'  => 0.00,
                'late_fee'           => 0.00, 'days_late'      => 0,
                'status'             => 'pending',
            ];

            $dueDate->modify($modifier);
        }

        $periodInterest = round($principal * $periodRate, 2);

        return [
            'installments'    => $installments,
            'monthly_payment' => $periodInterest,
            'total_interest'  => round($periodInterest * $termPeriods, 2),
            'total_payment'   => round($principal + $periodInterest * $termPeriods, 2),
            'monthly_rate'    => $monthlyRate,
            'period_rate'     => $periodRate,
            'annual_rate'     => $monthlyRate * 12,
            'frequency'       => $frequency,
            'note'            => 'Tipo C: cuota minima = solo interes. Capital se abona libremente.',
        ];
    }

    // ─── CALCULATE CURRENT INTEREST ──────────────────────────────────────────────
    // Para mostrar en la ficha del préstamo y en la pantalla de pagos.
    //
    // LÓGICA TIPO C:
    //   - Interés acumula por PERÍODOS transcurridos desde el primer vencimiento.
    //   - Si pasaron 2 meses sin pagar → se deben 2 × interés_período.
    //   - Mora adicional sobre el interés del primer período vencido.
    //
    // Ejemplo: L 10,000 al 20%/mes, vencido 60 días:
    //   Períodos = ceil(60/30) = 2
    //   Interés acumulado = 10,000 × 20% × 2 = L 4,000
    //   Mora = 2,000 × (5%/30) × 57 días efectivos = L 190
    //   Total = L 4,190  (sin contar capital)
    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array
    {
        $balance     = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $graceDays   = (int)setting('grace_days', 3);
        $frequency   = $loan['payment_frequency'] ?? 'monthly';
        $freqDays    = self::FREQ_DAYS[$frequency] ?? 30;
        $periodRate  = $monthlyRate * ($freqDays / 30);
        $periodInterest = round($balance * $periodRate, 2); // interés de 1 período

        // Primer vencimiento del préstamo
        $firstDue = new \DateTime($loan['first_payment_date'] ?? $loan['disbursement_date']);

        $daysOverdue    = 0;
        $periodsElapsed = 0;
        $accumulatedInterest = $periodInterest; // mínimo 1 período
        $maxDaysLate    = 0;
        $totalLateFee   = 0;

        if ($asOf > $firstDue) {
            // Cuántos días han pasado desde el primer vencimiento
            $daysOverdue = (int)$firstDue->diff($asOf)->days;
            $maxDaysLate = $daysOverdue;

            // Períodos transcurridos sin pagar (redondeado arriba)
            // ej: 60 días con frecuencia mensual (30 días) = 2 períodos
            $periodsElapsed = (int)ceil($daysOverdue / $freqDays);

            // Interés acumulado = balance × tasa × períodos
            $accumulatedInterest = round($balance * $periodRate * max(1, $periodsElapsed), 2);

            // Mora: sobre el interés del primer período, por los días efectivos con mora
            $effectiveDays = max(0, $daysOverdue - $graceDays);
            if ($effectiveDays > 0) {
                $totalLateFee = round(
                    $periodInterest * ((float)$loan['late_fee_rate'] / 30) * $effectiveDays,
                    2
                );
            }
        }

        // Interés ya pagado (de cuotas previas pagadas)
        $paidInterest = (float)\App\Core\DB::row(
            "SELECT COALESCE(SUM(pi.amount),0) as total
             FROM payment_items pi
             JOIN payments p ON p.id = pi.payment_id
             WHERE p.loan_id = ? AND pi.item_type = 'interest' AND p.voided = 0",
            [$loan['id']]
        )['total'];

        $pendingInterest = max(0, round($accumulatedInterest - $paidInterest, 2));

        return [
            'balance'             => $balance,
            'period_interest'     => $periodInterest,       // 1 período (referencia)
            'accumulated_interest'=> $accumulatedInterest,  // total acumulado real
            'paid_interest'       => $paidInterest,
            'pending_interest'    => $pendingInterest,       // lo que falta por pagar
            'late_fee'            => $totalLateFee,
            'total_due'           => round($pendingInterest + $totalLateFee, 2), // sin capital
            'total_due_with_cap'  => round($balance + $pendingInterest + $totalLateFee, 2),
            'balance_plus_period' => round($balance + $periodInterest, 2),
            'days_late'           => $maxDaysLate,
            'periods_elapsed'     => $periodsElapsed,
        ];
    }

    // ─── APPLY PAYMENT ───────────────────────────────────────────────────────────
    // Prioridad: mora → interés acumulado pendiente → capital (voluntario)
    //
    // IMPORTANTE para Tipo C:
    //   El interés acumulado puede ser MAYOR al que figura en las cuotas del DB
    //   (ej: 60 días vencido = L 4,000 en interés aunque la cuota diga L 2,000).
    //   Se cobra el interés acumulado REAL y luego se aplica a las cuotas.
    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array
    {
        $remaining   = $amount;
        $balance     = (float)$loan['balance'];
        $monthlyRate = (float)$loan['interest_rate'];
        $graceDays   = (int)setting('grace_days', 3);
        $frequency   = $loan['payment_frequency'] ?? 'monthly';
        $freqDays    = self::FREQ_DAYS[$frequency] ?? 30;
        $periodRate  = $monthlyRate * ($freqDays / 30);
        $items       = [];

        // Calcular estado actual (interés acumulado real)
        $state = $this->calculateCurrentInterest($loan, $paymentDate);

        // ── 1. MORA ──────────────────────────────────────────────────────────────
        $paidLateFee = 0;
        if ($state['late_fee'] > 0 && $remaining > 0) {
            $paidLateFee = min($remaining, $state['late_fee']);
            $remaining  -= $paidLateFee;
        }

        // ── 2. INTERÉS ACUMULADO PENDIENTE ───────────────────────────────────────
        // Puede ser más de 1 período (ej: 60 días → 2 períodos = L 4,000)
        $paidInterest = 0;
        $pendingInt   = $state['pending_interest'];

        if ($pendingInt > 0 && $remaining > 0) {
            $paidInterest = min($remaining, $pendingInt);
            $remaining   -= $paidInterest;
        }

        // Distribuir el pago de interés y mora entre las cuotas del DB
        // (para mantener el historial correcto por cuota)
        $installments = \App\Core\DB::all(
            "SELECT * FROM loan_installments
             WHERE loan_id = ? AND status IN ('pending','partial')
             ORDER BY due_date ASC",
            [$loan['id']]
        );

        $remainingInt     = $paidInterest;
        $remainingLateFee = $paidLateFee;

        foreach ($installments as $inst) {
            if ($remainingInt <= 0 && $remainingLateFee <= 0) break;

            $pendingInstInt = round((float)$inst['interest_amount'] - (float)$inst['paid_interest'], 2);

            // Mora de esta cuota
            $instLateFee    = 0;
            $paidInstLateFee= 0;
            $dueDateTime    = new \DateTime($inst['due_date']);
            $daysDiff       = (int)$dueDateTime->diff($paymentDate)->days;
            $isOverdue      = $paymentDate > $dueDateTime;

            if ($isOverdue && $daysDiff > $graceDays && $pendingInstInt > 0) {
                $effectiveDays = $daysDiff - $graceDays;
                $instLateFee   = round(
                    $pendingInstInt * ((float)$loan['late_fee_rate'] / 30) * $effectiveDays,
                    2
                );
            }

            if ($remainingLateFee > 0 && $instLateFee > 0) {
                $paidInstLateFee  = min($remainingLateFee, $instLateFee);
                $remainingLateFee -= $paidInstLateFee;
            }

            $paidInstInt = 0;
            if ($remainingInt > 0 && $pendingInstInt > 0) {
                $paidInstInt  = min($remainingInt, $pendingInstInt);
                $remainingInt -= $paidInstInt;
            }

            // Si hubo más interés que el de esta cuota (períodos extra acumulados),
            // añadir cuotas de interés adicionales como 'extra_interest'
            $totalApplied = $paidInstInt + $paidInstLateFee;
            if ($totalApplied <= 0) continue;

            $instPaid = ($paidInstInt >= $pendingInstInt);
            $status   = $instPaid ? 'paid' : 'partial';

            $items[] = [
                'installment_id' => $inst['id'],
                'paid_capital'   => 0,
                'paid_interest'  => $paidInstInt,
                'paid_late_fee'  => $paidInstLateFee,
                'total_applied'  => $totalApplied,
                'new_status'     => $status,
                'days_late'      => $daysDiff,
                'late_fee'       => $instLateFee,
            ];
        }

        // Si sobró interés (períodos acumulados sin cuota en DB), registrar como extra
        if ($remainingInt > 0) {
            $lastInst = end($installments);
            $items[] = [
                'installment_id' => $lastInst['id'] ?? null,
                'paid_capital'   => 0,
                'paid_interest'  => $remainingInt,
                'paid_late_fee'  => 0,
                'total_applied'  => $remainingInt,
                'new_status'     => 'extra_interest',
                'days_late'      => 0,
                'late_fee'       => 0,
            ];
            $remainingInt = 0;
        }

        // ── 3. ABONO VOLUNTARIO DE CAPITAL ───────────────────────────────────────
        // Solo después de cubrir todo el interés acumulado.
        if ($remaining > 0 && $balance > 0) {
            $extraCap  = min($remaining, $balance);
            $remaining -= $extraCap;
            $balance    = max(0.0, round($balance - $extraCap, 2));

            $lastPending = \App\Core\DB::row(
                "SELECT id FROM loan_installments
                 WHERE loan_id = ? AND status IN ('pending','partial')
                 ORDER BY due_date DESC LIMIT 1",
                [$loan['id']]
            );

            $items[] = [
                'installment_id' => $lastPending['id'] ?? null,
                'paid_capital'   => $extraCap,
                'paid_interest'  => 0,
                'paid_late_fee'  => 0,
                'total_applied'  => $extraCap,
                'new_status'     => 'extra_capital',
                'days_late'      => 0,
                'late_fee'       => 0,
            ];

            if ($balance > 0) {
                $this->recalculatePendingInstallments((int)$loan['id'], $balance, $periodRate);
            }
        }

        return [
            'items'       => $items,
            'new_balance' => round(max(0, $balance), 2),
            'remainder'   => round($remaining, 2),
            'state'       => $state, // para audit log
        ];
    }

    // ─── RECALCULAR CUOTAS PENDIENTES ────────────────────────────────────────────
    private function recalculatePendingInstallments(int $loanId, float $newBalance, float $periodRate): void
    {
        $pending = \App\Core\DB::all(
            "SELECT id, installment_number FROM loan_installments
             WHERE loan_id = ? AND status IN ('pending','partial')
             ORDER BY installment_number ASC",
            [$loanId]
        );

        $count = count($pending);
        foreach ($pending as $idx => $inst) {
            $isLast      = ($idx === $count - 1);
            $newInterest = round($newBalance * $periodRate, 2);
            $newCapital  = $isLast ? $newBalance : 0.00;
            $newTotal    = round($newCapital + $newInterest, 2);

            \App\Core\DB::update('loan_installments', [
                'interest_amount'  => $newInterest,
                'principal_amount' => $newCapital,
                'total_amount'     => $newTotal,
                'balance_after'    => $isLast ? 0.00 : $newBalance,
            ], 'id = ?', [$inst['id']]);
        }
    }
}