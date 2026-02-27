<?php
// app/models/Client.php

namespace App\Models;

use App\Core\DB;

class Client
{
    public static function all(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $s = "%{$filters['search']}%";
            $where[]  = "(CONCAT(c.first_name,' ',c.last_name) LIKE ? OR c.code LIKE ? OR c.identity_number LIKE ? OR c.phone LIKE ?)";
            $params   = array_merge($params, [$s, $s, $s, $s]);
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'c.is_active = ?'; $params[] = $filters['is_active'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'c.assigned_to = ?'; $params[] = $filters['assigned_to'];
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $sql = "SELECT c.id, c.code, c.first_name, c.last_name, c.identity_number,
                       c.email, c.phone, c.city, c.is_active, c.created_at,
                       u.name as assigned_name,
                       (SELECT COUNT(*) FROM loans l WHERE l.client_id = c.id AND l.status = 'active') as active_loans,
                       (SELECT COALESCE(SUM(l.balance),0) FROM loans l WHERE l.client_id = c.id AND l.status = 'active') as total_balance
                FROM clients c
                LEFT JOIN users u ON u.id = c.assigned_to
                WHERE $whereStr
                ORDER BY c.last_name, c.first_name
                LIMIT $perPage OFFSET $offset";

        $countSql = "SELECT COUNT(*) FROM clients c WHERE $whereStr";
        $total    = (int)array_values(DB::row($countSql, $params))[0];
        $data     = DB::all($sql, $params);

        return ['data' => $data, 'total' => $total, 'per_page' => $perPage,
                'current_page' => $page, 'last_page' => max(1, (int)ceil($total / $perPage))];
    }

    public static function find(int $id): ?array
    {
        return DB::row(
            "SELECT c.*, CONCAT(c.first_name,' ',c.last_name) as full_name,
                    u.name as assigned_name, cb.name as created_by_name
             FROM clients c
             LEFT JOIN users u  ON u.id  = c.assigned_to
             LEFT JOIN users cb ON cb.id = c.created_by
             WHERE c.id = ?",
            [$id]
        );
    }

    public static function create(array $data, int $createdBy): int
    {
        $code = self::generateCode();
        return (int)DB::insert('clients', [
            'code'          => $code,
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'identity_number'=> $data['identity_number'] ?? null,
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'phone2'        => $data['phone2'] ?? null,
            'address'       => $data['address'] ?? null,
            'city'          => $data['city'] ?? null,
            'occupation'    => $data['occupation'] ?? null,
            'monthly_income'=> $data['monthly_income'] ?? null,
            'reference_name'=> $data['reference_name'] ?? null,
            'reference_phone'=> $data['reference_phone'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'assigned_to'   => $data['assigned_to'] ?: null,
            'is_active'     => 1,
            'created_by'    => $createdBy,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $allowed = ['first_name','last_name','identity_number','email','phone','phone2',
                    'address','city','occupation','monthly_income','reference_name',
                    'reference_phone','notes','assigned_to','is_active'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if (!empty($update)) DB::update('clients', $update, 'id = ?', [$id]);
    }

    public static function delete(int $id): bool
    {
        // Check for active loans
        $active = DB::row("SELECT COUNT(*) as c FROM loans WHERE client_id = ? AND status = 'active'", [$id]);
        if ($active && $active['c'] > 0) return false;
        DB::update('clients', ['is_active' => 0], 'id = ?', [$id]);
        return true;
    }

    public static function getLoans(int $clientId): array
    {
        return DB::all(
            "SELECT l.*, u.name as assigned_name
             FROM loans l
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE l.client_id = ?
             ORDER BY l.created_at DESC",
            [$clientId]
        );
    }

    public static function getDocuments(int $clientId): array
    {
        return DB::all(
            "SELECT cd.*, u.name as uploaded_by_name
             FROM client_documents cd
             JOIN users u ON u.id = cd.uploaded_by
             WHERE cd.client_id = ?
             ORDER BY cd.doc_type, cd.created_at DESC",
            [$clientId]
        );
    }

    private static function generateCode(): string
    {
        $prefix = setting('client_number_prefix', 'CLI-');
        $last   = DB::row("SELECT MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)) as last_num FROM clients WHERE code LIKE ?",
                          [strlen($prefix) + 1, $prefix . '%']);
        $num    = (($last['last_num'] ?? 0) + 1);
        return $prefix . str_pad((string)$num, 5, '0', STR_PAD_LEFT);
    }
}