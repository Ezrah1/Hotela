<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Running tenants table migration...\n\n";

// Run the tenants table migration
$migration = require __DIR__ . '/../database/migrations/2025_11_14_080000_create_tenants_table.php';

foreach ($migration as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Tenants table created successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate table') !== false) {
            echo "⚠ Tenants table already exists (skipping)\n";
        } else {
            die("✗ Error: " . $e->getMessage() . "\n");
        }
    }
}

// Mark migration as run
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    
    $stmt = $pdo->prepare('INSERT IGNORE INTO migrations (migration, batch) VALUES (:migration, 1)');
    $stmt->execute(['migration' => '2025_11_14_080000_create_tenants_table.php']);
    echo "✓ Migration marked as run\n";
} catch (PDOException $e) {
    echo "⚠ Could not mark migration as run: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";

