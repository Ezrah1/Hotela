<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Support/helpers.php';

define('BASE_PATH', dirname(__DIR__));

$dotenvPath = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$name, $value] = array_map('trim', $parts);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$config = require config_path('app.php');

date_default_timezone_set($config['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

return [
    'config' => $config,
];


