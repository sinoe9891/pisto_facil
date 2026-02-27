<?php
// app/models/Setting.php

namespace App\Models;

use App\Core\DB;

class Setting
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $row = DB::row("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?", [$key]);
        if (!$row) return $default;
        $value = self::cast($row['setting_value'], $row['setting_type']);
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, mixed $value, int $userId = null): void
    {
        $strVal = is_array($value) ? json_encode($value) : (string)$value;
        DB::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)",
            [$key, $strVal, $userId]
        );
        self::$cache[$key] = $value;
    }

    public static function allByGroup(string $group = null): array
    {
        $sql    = "SELECT * FROM settings";
        $params = [];
        if ($group) { $sql .= " WHERE `group` = ?"; $params[] = $group; }
        $sql .= " ORDER BY `group`, setting_key";
        return DB::all($sql, $params);
    }

    private static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int)$value,
            'decimal' => (float)$value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}