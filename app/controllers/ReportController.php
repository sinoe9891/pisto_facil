<?php
// app/controllers/ReportController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, DB};
use App\Models\{User, Client};

class ReportController extends Controller
{
    // GENERAL REPORT
    public function general(): void
    {
        $from     = $this->get('from', date('Y-m-01'));
        $to       = $this->get('to', date('Y-m-d'));
        $status   = $this->get('status', '');
        $loanType = $this->get('loan_type', '');
        $advisor  = $this->get('advisor', '');

        $where  = ['l.disbursement_date BETWEEN ? AND ?'];
        $params = [$from, $to];

        if ($status)   { $where[] = 'l.status = ?';    $params[] = $status; }
        if ($loanType) { $where[] = 'l.loan_type = ?'; $params[] = $loanType; }
        if ($advisor)  { $where[] = 'l.assigned_to = ?'; $params[] = $advisor; }

        $w = implode(' AND ', $where);

        $summary = DB::row(
            "SELECT COUNT(*) as total_loans,
                    COALESCE(SUM(l.principal),0)          as total_principal,
                    COALESCE(SUM(l.total_paid),0)         as total_collected,
                    COALESCE(SUM(l.total_interest_paid),0)as total_interest,
                    COALESCE(SUM(l.total_late_fees_paid),0)as total_late_fees,
                    COALESCE(SUM(l.balance),0)            as total_outstanding,
                    SUM(l.status='active')  as active_count,
                    SUM(l.status='paid')    as paid_count,
                    SUM(l.status='defaulted')as defaulted_count
             FROM loans l WHERE $w", $params
        );

        $loans = DB::all(
            "SELECT l.id, l.loan_number, l.loan_type, l.principal, l.balance,
                    l.interest_rate, l.term_months, l.status, l.disbursement_date,
                    l.total_paid, l.total_interest_paid, l.total_late_fees_paid,
                    CONCAT(c.first_name,' ',c.last_name) as client_name,
                    c.identity_number, c.phone,
                    u.name as advisor_name
             FROM loans l
             JOIN clients c ON c.id = l.client_id
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE $w
             ORDER BY l.disbursement_date DESC",
            $params
        );

        // Monthly payments chart data
        $chartData = DB::all(
            "SELECT DATE_FORMAT(payment_date,'%Y-%m') as month,
                    COALESCE(SUM(total_received),0) as collected
             FROM payments
             WHERE voided = 0 AND payment_date BETWEEN ? AND ?
             GROUP BY month ORDER BY month",
            [$from, $to]
        );

        $advisors = User::allAdvisors();

        $this->render('reports/general', [
            'title'     => 'Reporte General',
            'summary'   => $summary,
            'loans'     => $loans,
            'chartData' => $chartData,
            'advisors'  => $advisors,
            'filters'   => compact('from','to','status','loanType','advisor'),
        ]);
    }

    // CLIENT REPORT
    public function client(string $id): void
    {
        $client = Client::find((int)$id);
        if (!$client) $this->redirect('/reports/general');

        $loans = DB::all(
            "SELECT l.*, u.name as advisor_name FROM loans l
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE l.client_id = ? ORDER BY l.created_at DESC",
            [(int)$id]
        );

        $payments = DB::all(
            "SELECT p.*, l.loan_number, u.name as registered_by_name
             FROM payments p
             JOIN loans l ON l.id = p.loan_id
             JOIN users u ON u.id = p.registered_by
             WHERE l.client_id = ? AND p.voided = 0
             ORDER BY p.payment_date DESC",
            [(int)$id]
        );

        $overdueInstallments = DB::all(
            "SELECT li.*, l.loan_number
             FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE l.client_id = ? AND li.status IN ('pending','partial') AND li.due_date < CURDATE()
             ORDER BY li.due_date",
            [(int)$id]
        );

        $totalBalance  = array_sum(array_column($loans, 'balance'));
        $totalPaid     = array_sum(array_column($payments, 'total_received'));
        $totalOverdue  = array_sum(array_map(fn($i) => $i['total_amount'] - $i['paid_amount'], $overdueInstallments));
        $daysInDefault = $overdueInstallments ? (int)(new \DateTime($overdueInstallments[0]['due_date']))->diff(new \DateTime())->days : 0;

        $this->render('reports/client', [
            'title'              => 'Reporte: ' . $client['full_name'],
            'client'             => $client,
            'loans'              => $loans,
            'payments'           => $payments,
            'overdueInstallments'=> $overdueInstallments,
            'totalBalance'       => $totalBalance,
            'totalPaid'          => $totalPaid,
            'totalOverdue'       => $totalOverdue,
            'daysInDefault'      => $daysInDefault,
        ]);
    }

    // PROJECTION REPORT
    public function projection(): void
    {
        $capital       = (float)$this->get('capital', setting('initial_capital', 200000));
        $rate          = (float)$this->get('rate', 20);   // % monthly
        $months        = (int)$this->get('months', 12);

        $monthlyRate   = $rate / 100;
        $projections   = [];
        $balance       = $capital;

        for ($m = 1; $m <= $months; $m++) {
            $interest    = round($balance * $monthlyRate, 2);
            $projections[] = [
                'month'    => $m,
                'balance'  => $balance,
                'interest' => $interest,
                'total'    => round($balance + $interest, 2),
            ];
            $balance = round($balance + $interest, 2);
        }

        $totalProjected = end($projections)['total'] ?? $capital;
        $totalGain      = $totalProjected - $capital;

        $this->render('reports/projection', [
            'title'           => 'Proyección de Ganancia',
            'capital'         => $capital,
            'rate'            => $rate,
            'months'          => $months,
            'projections'     => $projections,
            'totalProjected'  => $totalProjected,
            'totalGain'       => $totalGain,
        ]);
    }

    // CSV EXPORT
    public function export(): void
    {
        $from     = $this->get('from', date('Y-m-01'));
        $to       = $this->get('to', date('Y-m-d'));
        $status   = $this->get('status', '');
        $loanType = $this->get('loan_type', '');

        $where  = ['l.disbursement_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if ($status)   { $where[] = 'l.status = ?';    $params[] = $status; }
        if ($loanType) { $where[] = 'l.loan_type = ?'; $params[] = $loanType; }
        $w = implode(' AND ', $where);

        $loans = DB::all(
            "SELECT l.loan_number, l.loan_type, l.principal, l.interest_rate, l.balance,
                    l.status, l.disbursement_date, l.total_paid, l.total_interest_paid,
                    CONCAT(c.first_name,' ',c.last_name) as cliente, c.identity_number,
                    c.phone, u.name as asesor
             FROM loans l
             JOIN clients c ON c.id = l.client_id
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE $w ORDER BY l.disbursement_date DESC", $params
        );

        $filename = 'reporte_prestamos_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // BOM for Excel
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['# Préstamo','Tipo','Cliente','DNI','Teléfono','Asesor',
                       'Principal','Balance','Total Pagado','Interés Pagado',
                       'Estado','Desembolso']);
        foreach ($loans as $l) {
            fputcsv($out, [
                $l['loan_number'], 'Tipo '.$l['loan_type'], $l['cliente'],
                $l['identity_number'], $l['phone'], $l['asesor'],
                $l['principal'], $l['balance'], $l['total_paid'],
                $l['total_interest_paid'], $l['status'], $l['disbursement_date'],
            ]);
        }
        fclose($out);
        exit;
    }
}
