<?php
// app/models/Aval.php

namespace App\Models;

use App\Core\DB;

class Aval
{
    public static function allByClient(int $clientId): array
    {
        return DB::all(
            "SELECT id, client_id, full_name, identity_number, phone, relationship
             FROM avales
             WHERE client_id = ?
             ORDER BY full_name",
            [$clientId]
        );
    }

    public static function find(int $id): ?array
    {
        return DB::row("SELECT * FROM avales WHERE id = ?", [$id]);
    }

    public static function belongsToClient(int $avalId, int $clientId): bool
    {
        $row = DB::row(
            "SELECT id FROM avales WHERE id = ? AND client_id = ? LIMIT 1",
            [$avalId, $clientId]
        );
        return (bool)$row;
    }
}