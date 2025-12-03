<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();
$migrationsDir = base_path('database/migrations');

if (!is_dir($migrationsDir)) {
    echo "No migrations directory found.\n";
    exit(0);
}

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
sort($files);

$batch = (int)$pdo->query('SELECT IFNULL(MAX(batch), 0) FROM migrations')->fetchColumn() + 1;
$ran = 0;

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    $statements = include $file;
    if (!is_array($statements)) {
        echo "Skipping {$name}: migration file must return an array of SQL statements.\n";
        continue;
    }

    try {
        $hasErrors = false;
        foreach ($statements as $sql) {
            try {
                // Skip empty or comment-only SQL statements
                if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
                    continue;
                }
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // For tenant removal migration, continue on errors (columns/indexes may not exist)
                if (str_contains($name, 'remove_tenant_system')) {
                    $hasErrors = true;
                    echo "  Warning: {$e->getMessage()}\n";
                    continue;
                }
                // For audit columns migration, skip if columns already exist
                if (str_contains($name, 'add_audit_columns') && str_contains($e->getMessage(), 'Duplicate column')) {
                    $hasErrors = true;
                    echo "  Warning: {$e->getMessage()} (skipping)\n";
                    continue;
                }
                throw $e;
            }
        }
        $stmt = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
        $stmt->execute([$name, $batch]);
        $ran++;
        if ($hasErrors) {
            echo "Migrated: {$name} (with warnings)\n";
        } else {
            echo "Migrated: {$name}\n";
        }
    } catch (Throwable $e) {
        echo "Failed migrating {$name}: {$e->getMessage()}\n";
        exit(1);
    }
}

if ($ran === 0) {
    echo "Nothing to migrate.\n";
} else {
    echo "Migrations complete. ({$ran} new)\n";
}


