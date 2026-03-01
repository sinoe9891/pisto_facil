<?php
// app/models/Loan.php

namespace App\Models;

use App\Core\DB;

class Loan
{
    public static function find(int $id): ?array
    {
        return DB::row(
            "SELECT l.*, c.first_name, c.last_name, CONCAT(c.first_name,' ',c.last_name) as client_name,
                    c.id as client_id, c.phone as client_phone,
                    u.name as assigned_name, cb.name as created_by_name
             FROM loans l
             JOIN clients c ON c.id = l.client_id
             LEFT JOIN users u  ON u.id  = l.assigned_to
             LEFT JOIN users cb ON cb.id = l.created_by
             WHERE l.id = ?",
            [$id]
        );
    }

    public static function all(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        // Por defecto NO mostrar archivados (deleted) a menos que el usuario lo filtre explícitamente
        if (empty($filters['status'])) {
            $where[] = "l.status <> 'deleted'";
        }

        if (!empty($filters['status'])) {
            $where[] = 'l.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['loan_type'])) {
            $where[] = 'l.loan_type = ?';
            $params[] = $filters['loan_type'];
        }
        if (!empty($filters['search'])) {
            $s = "%{$filters['search']}%";
            $where[]  = "(l.loan_number LIKE ? OR CONCAT(c.first_name,' ',c.last_name) LIKE ?)";
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'l.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'l.client_id = ?';
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['filter']) && $filters['filter'] === 'overdue') {
            $where[] = "EXISTS (SELECT 1 FROM loan_installments li WHERE li.loan_id = l.id AND li.due_date < CURDATE() AND li.status IN ('pending','partial'))";
        }
        if (!empty($filters['filter']) && $filters['filter'] === 'upcoming') {
            $alertDays = (int)setting('alert_days_upcoming', 7);
            $where[]   = "EXISTS (SELECT 1 FROM loan_installments li WHERE li.loan_id = l.id AND li.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $alertDays DAY) AND li.status IN ('pending','partial'))";
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;
        $sql = "SELECT l.id, l.loan_number, l.loan_type, l.principal, l.balance, l.interest_rate,
                       l.term_months, l.status, l.disbursement_date, l.created_at,
                       CONCAT(c.first_name,' ',c.last_name) as client_name, c.id as client_id,
                       u.name as assigned_name
                FROM loans l
                JOIN clients c ON c.id = l.client_id
                LEFT JOIN users u ON u.id = l.assigned_to
                WHERE $whereStr
                ORDER BY l.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $count = (int)array_values(DB::row("SELECT COUNT(*) FROM loans l JOIN clients c ON c.id = l.client_id WHERE $whereStr", $params))[0];
        $data  = DB::all($sql, $params);

        return [
            'data' => $data,
            'total' => $count,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int)ceil($count / $perPage))
        ];
    }

    public static function create(array $data, int $createdBy): int
    {
        $prefix = setting('loan_number_prefix', 'PRES-');
        $last   = DB::row(
            "SELECT MAX(CAST(SUBSTRING(loan_number, ?) AS UNSIGNED)) as n FROM loans WHERE loan_number LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        );
        $number = $prefix . str_pad((string)(($last['n'] ?? 0) + 1), 6, '0', STR_PAD_LEFT);

        // Helper: convierte '' o '0' a null para campos opcionales enteros
        $nullableInt = fn($v) => (isset($v) && $v !== '' && (int)$v > 0) ? (int)$v : null;
        $nullableStr = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim($v) : null;

        return (int)DB::insert('loans', [
            'client_id'         => (int)$data['client_id'],
            'assigned_to'       => $nullableInt($data['assigned_to'] ?? null),
            'created_by'        => $createdBy,
            'loan_number'       => $number,
            'loan_type'         => $data['loan_type'],
            'principal'         => (float)$data['principal'],
            'interest_rate'     => (float)$data['interest_rate'],
            'rate_type'         => $data['rate_type'] ?? 'monthly',
            'term_months'       => $nullableInt($data['term_months'] ?? null),
            'late_fee_rate'     => (float)($data['late_fee_rate'] ?? setting('default_late_fee_rate', 0.05)),
            'grace_days'        => (int)($data['grace_days'] ?? setting('grace_days', 3)),
            'disbursement_date' => $data['disbursement_date'],
            'first_payment_date' => $data['first_payment_date'],
            'maturity_date'     => $nullableStr($data['maturity_date'] ?? null),
            'status'            => 'active',
            'balance'           => (float)$data['principal'],
            'apply_payment_to'  => $data['apply_payment_to'] ?? 'interest_first',
            'notes'             => $nullableStr($data['notes'] ?? null),
        ]);
    }

    public static function getInstallments(int $loanId): array
    {
        return DB::all(
            "SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number",
            [$loanId]
        );
    }

    public static function getPayments(int $loanId): array
    {
        return DB::all(
            "SELECT p.*, u.name as registered_by_name
             FROM payments p JOIN users u ON u.id = p.registered_by
             WHERE p.loan_id = ? AND p.voided = 0
             ORDER BY p.payment_date DESC",
            [$loanId]
        );
    }

    public static function updateBalance(int $id, float $newBalance, array $extras = []): void
    {
        $data = ['balance' => $newBalance, ...$extras];
        DB::update('loans', $data, 'id = ?', [$id]);
    }

    public static function markPaidIfComplete(int $id): void
    {
        $loan = self::find($id);
        if (!$loan) return;
        if ($loan['balance'] <= 0.01) {
            DB::update('loans', ['status' => 'paid', 'last_payment_date' => date('Y-m-d')], 'id = ?', [$id]);
        }
    }
}
