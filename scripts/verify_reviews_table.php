<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Checking reviews table structure...\n\n";

try {
    $stmt = $pdo->query('DESCRIBE reviews');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table 'reviews' exists with the following columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓ Reviews table is ready!\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

