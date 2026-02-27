<?php
// app/core/Validator.php

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** Static factory */
    public static function make(array $data, array $rules): static
    {
        $v = new static($data);
        foreach ($rules as $field => $ruleStr) {
            $v->applyRules($field, $ruleStr);
        }
        return $v;
    }

    private function applyRules(string $field, string $ruleStr): void
    {
        $rules = explode('|', $ruleStr);
        $value = $this->data[$field] ?? null;

        foreach ($rules as $rule) {
            [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($ruleName) {
                case 'required':
                    if ($value === null || trim((string)$value) === '') {
                        $this->errors[$field][] = "El campo {$field} es requerido.";
                    }
                    break;
                case 'email':
                    if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "El campo {$field} debe ser un email válido.";
                    }
                    break;
                case 'min':
                    if ($value !== null && strlen((string)$value) < (int)$param) {
                        $this->errors[$field][] = "El campo {$field} debe tener al menos {$param} caracteres.";
                    }
                    break;
                case 'max':
                    if ($value !== null && strlen((string)$value) > (int)$param) {
                        $this->errors[$field][] = "El campo {$field} no debe superar {$param} caracteres.";
                    }
                    break;
                case 'numeric':
                    if ($value !== null && $value !== '' && !is_numeric($value)) {
                        $this->errors[$field][] = "El campo {$field} debe ser numérico.";
                    }
                    break;
                case 'min_val':
                    if ($value !== null && is_numeric($value) && (float)$value < (float)$param) {
                        $this->errors[$field][] = "El campo {$field} debe ser al menos {$param}.";
                    }
                    break;
                case 'max_val':
                    if ($value !== null && is_numeric($value) && (float)$value > (float)$param) {
                        $this->errors[$field][] = "El campo {$field} no debe superar {$param}.";
                    }
                    break;
                case 'in':
                    $options = explode(',', $param ?? '');
                    if ($value !== null && $value !== '' && !in_array($value, $options, true)) {
                        $this->errors[$field][] = "El valor del campo {$field} no es válido.";
                    }
                    break;
                case 'date':
                    if ($value && !\DateTime::createFromFormat('Y-m-d', $value)) {
                        $this->errors[$field][] = "El campo {$field} debe ser una fecha válida (YYYY-MM-DD).";
                    }
                    break;
                case 'confirmed':
                    $confirm = $this->data[$field . '_confirmation'] ?? null;
                    if ($value !== $confirm) {
                        $this->errors[$field][] = "La confirmación del campo {$field} no coincide.";
                    }
                    break;
                case 'password_strength':
                    if ($value && !preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $value)) {
                        $this->errors[$field][] = "La contraseña debe tener 8+ caracteres, una mayúscula, un número y un símbolo.";
                    }
                    break;
                case 'unique':
                    // Usage: unique:table,column[,except_id]
                    [$table, $col, $exceptId] = array_pad(explode(',', $param ?? ''), 3, null);
                    if ($value) {
                        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$col` = ?";
                        $p   = [$value];
                        if ($exceptId) { $sql .= " AND id != ?"; $p[] = $exceptId; }
                        $count = DB::row($sql, $p);
                        if ($count && (int)array_values($count)[0] > 0) {
                            $this->errors[$field][] = "El valor del campo {$field} ya está en uso.";
                        }
                    }
                    break;
            }
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return '';
    }

    /** Return sanitized data (only validated fields) */
    public function validated(): array
    {
        $out = [];
        foreach (array_keys($this->data) as $key) {
            $val = $this->data[$key];
            $out[$key] = is_string($val) ? htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8') : $val;
        }
        return $out;
    }
}