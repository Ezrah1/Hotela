<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$migration = include __DIR__ . '/../database/migrations/2025_02_01_000000_create_audit_logs_table.php';

foreach ($migration as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Created audit_logs table\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            echo "⚠ Table already exists\n";
        } else {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

// Mark migration as run
try {
    $stmt = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
    $stmt->execute(['2025_02_01_000000_create_audit_logs_table.php', 1]);
    echo "✓ Migration marked as run\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        echo "⚠ Migration already marked as run\n";
    } else {
        echo "⚠ Could not mark migration: " . $e->getMessage() . "\n";
    }
}

echo "\nDone!\n";

