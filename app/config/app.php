<?php
// app/config/app.php

return [
    'name'        => $_ENV['APP_NAME']  ?? 'SistemaPréstamos',
    'url'         => $_ENV['APP_URL']   ?? 'http://localhost/loanapp/public',
    'env'         => $_ENV['APP_ENV']   ?? 'production',
    'debug'       => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'timezone'    => $_ENV['APP_TZ']    ?? 'America/Tegucigalpa',
    'secret_key'  => $_ENV['APP_KEY']   ?? 'change-me-in-production-32chars!!',
    'version'     => '1.0.0',

    // Uploads
    'upload_path'      => dirname(__DIR__, 2) . '/storage/uploads',
    'max_upload_bytes' => 10 * 1024 * 1024,   // 10 MB
    'allowed_mimes'    => [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ],

    // Session
    'session_lifetime' => 7200,   // 2 hours in seconds

    // Pagination
    'per_page' => 20,
];