<?php
// app/core/Auth.php

namespace App\Core;

class Auth
{
    /** Start or resume session securely */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $cfg = require APP_PATH . '/config/app.php';
            session_set_cookie_params([
                'lifetime' => $cfg['session_lifetime'],
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /** Log in a user and store in session */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'      => $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role_id' => $user['role_id'],
            'role'    => $user['role_slug'] ?? '',
        ];
        $_SESSION['logged_in_at'] = time();
    }

    /** Log out */
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        // Clear cookie
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
    }

    /** Check if user is logged in */
    public static function check(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    /** Return the current user array or null */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /** Return current user id */
    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /** Return current role slug */
    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    /** Check role(s) */
    public static function hasRole(string|array $roles): bool
    {
        $role = self::role();
        if ($role === null) return false;
        if (is_string($roles)) return $role === $roles;
        return in_array($role, $roles, true);
    }

    /** Redirect to login if not authenticated */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . url('/login'));
            exit;
        }
    }

    /** Redirect to dashboard if already logged in */
    public static function requireGuest(): void
    {
        if (self::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
    }

    /** Require a specific role */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            $view = new View();
            $view->render('errors/403', ['message' => 'No tiene permisos para acceder a esta sección.'], null);
            exit;
        }
    }

    /** Is SuperAdmin? */
    public static function isSuperAdmin(): bool
    {
        return self::hasRole('superadmin');
    }

    /** Can access admin features? */
    public static function isAdmin(): bool
    {
        return self::hasRole(['superadmin', 'admin']);
    }
}