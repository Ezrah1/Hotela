<?php

return [
    'name' => env('APP_NAME', 'Hotela'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', true),
    'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),
    'fallback_locale' => 'en',
    'url' => env('APP_URL', 'http://localhost/Hotela/public'),
    'db' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'hotela'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'providers' => [],
];


