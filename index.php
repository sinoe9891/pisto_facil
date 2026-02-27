<?php
declare(strict_types=1);

// ============================================================
// FRONT CONTROLLER - Sistema de Gestión de Préstamos
// ============================================================

define('ROOT_PATH', __DIR__);
define('APP_PATH',  ROOT_PATH . '/app');
define('PUB_PATH',  __DIR__);
define('APP_VERSION','1.0.0');

// Load .env (simple parser, no package needed)
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $_SERVER[$k] = $v;
    }
}

// Error handling
$cfg = require APP_PATH . '/config/app.php';
if ($cfg['debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Timezone
date_default_timezone_set($cfg['timezone'] ?? 'America/Tegucigalpa');

// PSR-4-like autoloader
spl_autoload_register(function (string $class): void {
    $map = [
        'App\Core\\'        => APP_PATH . '/core/',
        'App\Controllers\\' => APP_PATH . '/controllers/',
        'App\Models\\'      => APP_PATH . '/models/',
        'App\Services\\'    => APP_PATH . '/services/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file     = $dir . $relative . '.php';
            if (file_exists($file)) { require $file; return; }
        }
    }
});

// Start session
use App\Core\Auth;
Auth::start();

// Global helpers
function url(string $path = ''): string
{
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}

function setting(string $key, mixed $default = null): mixed
{
    return \App\Models\Setting::get($key, $default);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $amount, string $symbol = null): string
{
    $symbol ??= setting('app_currency', 'L');
    return $symbol . ' ' . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'd/m/Y'): string
{
    return $date ? date($format, strtotime($date)) : '';
}

function csrfField(): string
{
    return \App\Core\CSRF::field();
}

// ============================================================
// ROUTES
// ============================================================
use App\Core\Router;

$router = new Router();

// AUTH
$router->get('/login',  'AuthController@loginForm',  ['guest']);
$router->post('/login', 'AuthController@loginPost',  ['guest']);
$router->get('/logout', 'AuthController@logout',     ['auth']);

// DASHBOARD
$router->get('/dashboard', 'DashboardController@index', ['auth']);
$router->get('/',          'DashboardController@index', ['auth']);

// CLIENTS
$router->get( '/clients',              'ClientController@index',    ['auth','asesor']);
$router->get( '/clients/create',       'ClientController@create',   ['auth','admin']);
$router->post('/clients/store',        'ClientController@store',    ['auth','admin']);
$router->get( '/clients/{id}',         'ClientController@show',     ['auth','asesor']);
$router->get( '/clients/{id}/edit',    'ClientController@edit',     ['auth','admin']);
$router->post('/clients/{id}/update',  'ClientController@update',   ['auth','admin']);
$router->get( '/clients/{id}/delete',  'ClientController@delete',   ['auth','admin']);
$router->post('/clients/{id}/upload',  'ClientController@uploadDoc',['auth','admin']);
$router->get( '/clients/{id}/doc/{docId}/delete', 'ClientController@deleteDoc', ['auth','admin']);
$router->get( '/clients/{id}/doc/{docId}/download', 'ClientController@downloadDoc', ['auth','asesor']);

// LOANS
$router->get( '/loans',               'LoanController@index',      ['auth','asesor']);
$router->get( '/loans/create',        'LoanController@create',     ['auth','admin']);
$router->post('/loans/store',         'LoanController@store',      ['auth','admin']);
$router->get( '/loans/{id}',          'LoanController@show',       ['auth','asesor']);
$router->get( '/loans/{id}/edit',     'LoanController@edit',       ['auth','admin']);
$router->post('/loans/{id}/update',   'LoanController@update',     ['auth','admin']);
$router->get( '/loans/{id}/amortization', 'LoanController@amortization', ['auth','asesor']);

// PAYMENTS
$router->get( '/payments',             'PaymentController@index',   ['auth','asesor']);
$router->get( '/payments/create',      'PaymentController@create',  ['auth','asesor']);
$router->post('/payments/store',       'PaymentController@store',   ['auth','asesor']);
$router->get( '/payments/{id}',        'PaymentController@show',    ['auth','asesor']);
$router->get( '/payments/{id}/void',   'PaymentController@voidPayment', ['auth','admin']);

// USERS
$router->get( '/users',               'UserController@index',      ['auth','admin']);
$router->get( '/users/create',        'UserController@create',     ['auth','admin']);
$router->post('/users/store',         'UserController@store',      ['auth','admin']);
$router->get( '/users/{id}/edit',     'UserController@edit',       ['auth','admin']);
$router->post('/users/{id}/update',   'UserController@update',     ['auth','admin']);
$router->get( '/users/{id}/toggle',   'UserController@toggle',     ['auth','admin']);

// REPORTS
$router->get( '/reports/general',     'ReportController@general',  ['auth','admin']);
$router->get( '/reports/client/{id}', 'ReportController@client',   ['auth','admin']);
$router->get( '/reports/projection',  'ReportController@projection',['auth','superadmin']);
$router->get( '/reports/export',      'ReportController@export',   ['auth','admin']);

// SETTINGS
$router->get( '/settings',            'SettingController@index',   ['auth','superadmin']);
$router->post('/settings/update',     'SettingController@update',  ['auth','superadmin']);

// CLIENT PORTAL
$router->get('/my-loans',             'PortalController@index',    ['auth']);

// DISPATCH
$router->dispatch();