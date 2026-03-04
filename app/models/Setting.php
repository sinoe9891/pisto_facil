<?php
// app/models/Setting.php

namespace App\Models;

use App\Core\DB;

class Setting
{
    private static array $cache = [];
    
    // Campos bancarios que NO deben mostrarse en "General"
    private static array $excludeFromGeneral = [
        'bank_name_1', 'bank_name_2', 'bank_name_3',
        'bank_account_1', 'bank_account_2', 'bank_account_3',
        'bank_account_type_1', 'bank_account_type_2', 'bank_account_type_3',
        'bank_account_holder_1', 'bank_account_holder_2', 'bank_account_holder_3',
        'bank_account_iban_1', 'bank_account_iban_2', 'bank_account_iban_3',
    ];
    
    // Campos de documentos legales que NO deben mostrarse en "Documentos" (tienen su propia sección)
    private static array $excludeFromDocuments = [
        'contract_page_size', 'contract_margin_top', 'contract_margin_right', 'contract_jurisdiction',
        'pagare_page_size', 'pagare_margin_top', 'pagare_margin_right', 'pagare_jurisdiction', 'pagare_city',
    ];

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

    /**
     * Obtiene configuraciones por grupo, excluyendo campos que tienen secciones dedicadas
     */
    public static function allByGroup(string $group = null): array
    {
        $sql    = "SELECT * FROM settings";
        $params = [];
        
        if ($group) { 
            $sql .= " WHERE `group` = ?"; 
            $params[] = $group; 
        }
        
        $sql .= " ORDER BY `group`, setting_key";
        $results = DB::all($sql, $params);
        
        // Excluir campos que tienen secciones dedicadas
        $results = array_filter($results, function($item) use ($group) {
            if ($group === 'general' && in_array($item['setting_key'], self::$excludeFromGeneral)) {
                return false;
            }
            if ($group === 'documents' && in_array($item['setting_key'], self::$excludeFromDocuments)) {
                return false;
            }
            return true;
        });
        
        return array_values($results);
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