<?php
// app/core/CSRF.php

namespace App\Core;

class CSRF
{
    private const TOKEN_KEY  = '_csrf_token';
    private const TOKEN_LEN  = 32;

    /** Generate or return existing CSRF token */
    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(self::TOKEN_LEN));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /** HTML hidden input */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token()) . '">';
    }

    /** Validate token from POST */
    public static function verify(): bool
    {
        $submitted = $_POST['_csrf'] ?? '';
        $stored    = $_SESSION[self::TOKEN_KEY] ?? '';
        if (empty($submitted) || empty($stored)) return false;
        return hash_equals($stored, $submitted);
    }

    /** Verify or abort 419 */
    public static function check(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            die(json_encode(['error' => 'CSRF token inválido. Recargue la página e intente nuevamente.']));
        }
    }

    /** Regenerate token after use */
    public static function regenerate(): void
    {
        $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(self::TOKEN_LEN));
    }
}