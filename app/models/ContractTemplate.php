<?php
// app/models/ContractTemplate.php

namespace App\Models;

use App\Core\DB;

class ContractTemplate
{
    public static function all(): array
    {
        return DB::all("SELECT * FROM contract_templates ORDER BY id DESC");
    }

    public static function find(int $id): ?array
    {
        return DB::row("SELECT * FROM contract_templates WHERE id = ? LIMIT 1", [$id]);
    }

    public static function create(array $data): int
    {
        DB::query(
            "INSERT INTO contract_templates (name, template_type, content, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())",
            [
                $data['name'],
                $data['template_type'],
                $data['content'],
                (int)($data['is_active'] ?? 1),
            ]
        );

        $row = DB::row("SELECT LAST_INSERT_ID() AS id");
        return (int)($row['id'] ?? 0);
    }


    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];

        foreach (['name', 'template_type', 'content', 'is_active'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $params[] = $data[$k];
            }
        }
        if (!$fields) return;

        $params[] = $id;

        DB::query(
            "UPDATE contract_templates SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?",
            $params
        );
    }
}
