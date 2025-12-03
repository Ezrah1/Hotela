<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

// Check if table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'gallery_items'");
$exists = $stmt->fetch() !== false;

if ($exists) {
    echo "✓ gallery_items table already exists.\n";
    exit(0);
}

// Run the migration
$migrationFile = __DIR__ . '/../database/migrations/2025_11_24_010000_create_gallery_table.php';
if (!file_exists($migrationFile)) {
    echo "✗ Migration file not found: $migrationFile\n";
    exit(1);
}

$statements = include $migrationFile;
if (!is_array($statements)) {
    echo "✗ Migration file must return an array of SQL statements.\n";
    exit(1);
}

try {
    foreach ($statements as $sql) {
        if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
            continue;
        }
        $pdo->exec($sql);
    }
    
    // Mark migration as run
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $batch = (int)$pdo->query('SELECT IFNULL(MAX(batch), 0) FROM migrations')->fetchColumn() + 1;
    $stmt = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
    $stmt->execute(['2025_11_24_010000_create_gallery_table.php', $batch]);
    
    echo "✓ Successfully created gallery_items table.\n";
    echo "✓ Migration marked as run.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

