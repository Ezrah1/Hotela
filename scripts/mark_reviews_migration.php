<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

// Ensure migrations table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

// Get the current batch number
$batch = (int)$pdo->query('SELECT IFNULL(MAX(batch), 0) FROM migrations')->fetchColumn() + 1;

// Mark the migration as run
$stmt = $pdo->prepare('INSERT IGNORE INTO migrations (migration, batch) VALUES (:migration, :batch)');
$stmt->execute([
    'migration' => '2025_01_27_000001_create_reviews_table.php',
    'batch' => $batch,
]);

echo "Migration marked as run.\n";

