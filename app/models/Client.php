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
                       c.marital_status, c.nationality,
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
            'code'              => $code,
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'identity_number'   => $data['identity_number'] ?? null,
            'email'             => $data['email'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'phone2'            => $data['phone2'] ?? null,
            'work_phone'        => $data['work_phone'] ?? null,
            'nationality'       => $data['nationality'] ?? 'Hondureña',
            'profession'        => $data['profession'] ?? null,
            'address'           => $data['address'] ?? null,
            'city'              => $data['city'] ?? null,
            'occupation'        => $data['occupation'] ?? null,
            'monthly_income'    => $data['monthly_income'] ?? null,
            'marital_status'    => $data['marital_status'] ?? 'soltero',
            'spouse_name'       => $data['marital_status'] === 'casado' ? ($data['spouse_name'] ?? null) : null,
            'spouse_phone'      => $data['marital_status'] === 'casado' ? ($data['spouse_phone'] ?? null) : null,
            'spouse_identity'   => $data['marital_status'] === 'casado' ? ($data['spouse_identity'] ?? null) : null,
            'ref_personal_name' => $data['ref_personal_name'] ?? null,
            'ref_personal_phone'=> $data['ref_personal_phone'] ?? null,
            'ref_personal_rel'  => $data['ref_personal_rel'] ?? null,
            'ref_labor_name'    => $data['ref_labor_name'] ?? null,
            'ref_labor_phone'   => $data['ref_labor_phone'] ?? null,
            'ref_labor_company' => $data['ref_labor_company'] ?? null,
            // Paths se setean después del upload
            'identity_front_path' => $data['identity_front_path'] ?? null,
            'identity_back_path'  => $data['identity_back_path']  ?? null,
            'reference_name'    => $data['ref_personal_name'] ?? null,  // backward compat
            'reference_phone'   => $data['ref_personal_phone'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'assigned_to'       => $data['assigned_to'] ?: null,
            'is_active'         => 1,
            'created_by'        => $createdBy,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $allowed = [
            'first_name','last_name','identity_number','email','phone','phone2','work_phone',
            'nationality','profession','address','city','occupation','monthly_income',
            'marital_status','spouse_name','spouse_phone','spouse_identity',
            'ref_personal_name','ref_personal_phone','ref_personal_rel',
            'ref_labor_name','ref_labor_phone','ref_labor_company',
            'identity_front_path','identity_back_path',
            'reference_name','reference_phone',
            'notes','assigned_to','is_active',
        ];
        $update = array_intersect_key($data, array_flip($allowed));
        // Limpiar campos de cónyuge si no está casado
        if (isset($update['marital_status']) && $update['marital_status'] !== 'casado') {
            $update['spouse_name'] = null;
            $update['spouse_phone'] = null;
            $update['spouse_identity'] = null;
        }
        if (!empty($update)) DB::update('clients', $update, 'id = ?', [$id]);
    }

    public static function delete(int $id): bool
    {
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

    // ── AVAL ────────────────────────────────────────────────

    public static function getAval(int $clientId): ?array
    {
        return DB::row("SELECT * FROM avales WHERE client_id = ? LIMIT 1", [$clientId]);
    }

    public static function saveAval(int $clientId, array $data): int
    {
        $existing = self::getAval($clientId);
        $row = [
            'client_id'          => $clientId,
            'aval_client_id'     => $data['aval_client_id'] ?: null,
            'full_name'          => $data['aval_full_name'],
            'identity_number'    => $data['aval_identity'] ?? null,
            'phone'              => $data['aval_phone'] ?? null,
            'phone2'             => $data['aval_phone2'] ?? null,
            'address'            => $data['aval_address'] ?? null,
            'city'               => $data['aval_city'] ?? null,
            'occupation'         => $data['aval_occupation'] ?? null,
            'nationality'        => $data['aval_nationality'] ?? 'Hondureña',
            'relationship'       => $data['aval_relationship'] ?? null,
            'identity_front_path'=> $data['aval_identity_front_path'] ?? null,
            'identity_back_path' => $data['aval_identity_back_path']  ?? null,
            'notes'              => $data['aval_notes'] ?? null,
        ];
        if ($existing) {
            DB::update('avales', $row, 'client_id = ?', [$clientId]);
            return $existing['id'];
        } else {
            return (int)DB::insert('avales', $row);
        }
    }

    public static function deleteAval(int $clientId): void
    {
        DB::query("DELETE FROM avales WHERE client_id = ?", [$clientId]);
    }

    public static function findAvalById(int $avalId): ?array
    {
        return DB::row("SELECT * FROM avales WHERE id = ?", [$avalId]);
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