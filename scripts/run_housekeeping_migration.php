<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$statements = include __DIR__ . '/../database/migrations/2025_01_28_000000_create_housekeeping_tables.php';

if (!is_array($statements)) {
    echo "Error: migration file must return an array of SQL statements.\n";
    exit(1);
}

foreach ($statements as $sql) {
    try {
        if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
            continue;
        }
        $pdo->exec($sql);
        echo "Executed: " . substr($sql, 0, 80) . "...\n";
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || 
            str_contains($e->getMessage(), 'already exists') ||
            str_contains($e->getMessage(), 'Duplicate key') ||
            str_contains($e->getMessage(), 'Duplicate entry')) {
            echo "Warning: {$e->getMessage()} (skipping)\n";
            continue;
        }
        echo "Error: {$e->getMessage()}\n";
        // Don't exit on error, continue with other statements
    }
}

echo "\nHousekeeping tables migration complete.\n";

