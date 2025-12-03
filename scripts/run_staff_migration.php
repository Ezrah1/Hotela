<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$statements = include __DIR__ . '/../database/migrations/2025_01_28_000001_create_staff_table.php';

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
        echo "Executed: " . substr($sql, 0, 60) . "...\n";
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            echo "Warning: {$e->getMessage()} (skipping)\n";
            continue;
        }
        echo "Error: {$e->getMessage()}\n";
        exit(1);
    }
}

echo "Staff table migration complete.\n";

