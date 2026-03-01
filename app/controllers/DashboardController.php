<?php
// app/controllers/DashboardController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, DB};
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index(): void
    {
        $alertDays = (int)Setting::get('alert_days_upcoming', 7);
        $today     = date('Y-m-d');
        $upcomingDate = date('Y-m-d', strtotime("+{$alertDays} days"));
        $userId    = Auth::id();
        $role      = Auth::role();

        // Build scope filter for asesor
        $scopeWhere  = '';
        $scopeParams = [];
        if ($role === 'asesor') {
            $scopeWhere  = ' AND l.assigned_to = ?';
            $scopeParams = [$userId];
        }

        // ---- KEY METRICS ----
        $totalLoans = DB::row(
            "SELECT COUNT(*) as total, COALESCE(SUM(l.balance),0) as total_balance
             FROM loans l WHERE l.status = 'active' $scopeWhere",
            $scopeParams
        );

        $totalClients = DB::row(
            "SELECT COUNT(*) as total FROM clients WHERE is_active = 1"
        );

        $paymentsThisMonth = DB::row(
            "SELECT COUNT(*) as count, COALESCE(SUM(total_received),0) as total
             FROM payments
             WHERE voided = 0 AND DATE_FORMAT(payment_date,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"
        );

        $totalOverdue = DB::row(
            "SELECT COUNT(DISTINCT l.id) as loans_count, COALESCE(SUM(li.total_amount - li.paid_amount),0) as total_owed
             FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE li.due_date < ? AND li.status IN ('pending','partial') AND l.status = 'active' $scopeWhere",
            [$today, ...$scopeParams]
        );

        // ---- VENCIDAS ----
        $overdue = DB::all(
            "SELECT
               li.id as inst_id, li.loan_id, li.due_date, li.total_amount, li.paid_amount,
               li.total_amount - li.paid_amount as pending,
               DATEDIFF(CURDATE(), li.due_date) as days_late,
               l.loan_number, l.balance, l.interest_rate, l.late_fee_rate,
               CONCAT(c.first_name,' ',c.last_name) as client_name,
               c.id as client_id, c.phone as client_phone
             FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             JOIN clients c ON c.id = l.client_id
             WHERE li.due_date < ? AND li.status IN ('pending','partial') AND l.status = 'active' $scopeWhere
             ORDER BY li.due_date ASC
             LIMIT 20",
            [$today, ...$scopeParams]
        );

        // ---- POR VENCER ----
        $upcoming = DB::all(
            "SELECT
               li.id as inst_id, li.loan_id, li.due_date, li.total_amount, li.paid_amount,
               li.total_amount - li.paid_amount as pending,
               DATEDIFF(li.due_date, CURDATE()) as days_left,
               l.loan_number,
               CONCAT(c.first_name,' ',c.last_name) as client_name,
               c.id as client_id, c.phone as client_phone
             FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             JOIN clients c ON c.id = l.client_id
             WHERE li.due_date >= ? AND li.due_date <= ? AND li.status IN ('pending','partial') AND l.status = 'active' $scopeWhere
             ORDER BY li.due_date ASC
             LIMIT 20",
            [$today, $upcomingDate, ...$scopeParams]
        );

        // ---- RECENT PAYMENTS ----
        $recentPayments = DB::all(
            "SELECT p.id, p.payment_date, p.total_received, p.payment_method,
                    p.loan_id,
                    l.loan_number,
                    CONCAT(c.first_name,' ',c.last_name) as client_name,
                    u.name as registered_by
             FROM payments p
             JOIN loans l ON l.id = p.loan_id
             JOIN clients c ON c.id = l.client_id
             JOIN users u ON u.id = p.registered_by
             WHERE p.voided = 0
             ORDER BY p.created_at DESC
             LIMIT 8"
        );

        // ---- LOAN STATUS CHART DATA ----
        $loanStatusData = DB::all(
            "SELECT status, COUNT(*) as count FROM loans GROUP BY status"
        );

        $this->render('dashboard/index', [
            'title'            => 'Dashboard',
            'alertDays'        => $alertDays,
            'totalLoans'       => $totalLoans,
            'totalClients'     => $totalClients,
            'paymentsThisMonth'=> $paymentsThisMonth,
            'totalOverdue'     => $totalOverdue,
            'overdue'          => $overdue,
            'upcoming'         => $upcoming,
            'recentPayments'   => $recentPayments,
            'loanStatusData'   => $loanStatusData,
        ]);
    }
}
