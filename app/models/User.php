<?php
// app/models/User.php

namespace App\Models;

use App\Core\DB;

class User
{
    public static function findByEmail(string $email): ?array
    {
        return DB::row(
            "SELECT u.*, r.slug as role_slug, r.name as role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.is_active = 1",
            [$email]
        );
    }

    public static function findById(int $id): ?array
    {
        return DB::row(
            "SELECT u.*, r.slug as role_slug, r.name as role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?",
            [$id]
        );
    }

    public static function all(array $filters = []): array
    {
        $sql  = "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at, u.last_login, r.name as role_name, r.slug as role_slug
                 FROM users u JOIN roles r ON r.id = u.role_id WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND r.slug = ?"; $params[] = $filters['role'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        $sql .= " ORDER BY u.name";
        return DB::all($sql, $params);
    }

    public static function create(array $data): int
    {
        return (int)DB::insert('users', [
            'role_id'  => $data['role_id'],
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'phone'    => $data['phone'] ?? null,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        DB::update('users', $data, 'id = ?', [$id]);
    }

    public static function updateLastLogin(int $id): void
    {
        DB::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function verify(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password'])) return null;
        return $user;
    }

    public static function allAdvisors(): array
    {
        return DB::all(
            "SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug IN ('superadmin','admin','asesor') AND u.is_active = 1 ORDER BY u.name"
        );
    }
}