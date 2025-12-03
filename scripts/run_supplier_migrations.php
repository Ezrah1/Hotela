<?php

/**
 * Run supplier integration migrations
 * This script runs only the new supplier-related migrations
 */

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Running Supplier Integration Migrations...\n\n";

// Migration 1: Enhance suppliers table
echo "1. Enhancing suppliers table...\n";
$migration1 = include __DIR__ . '/../database/migrations/2025_01_28_000000_enhance_suppliers_table.php';

foreach ($migration1 as $index => $sql) {
    if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
        continue;
    }
    
    try {
        $pdo->exec($sql);
        echo "   ✓ Statement " . ($index + 1) . " executed successfully\n";
    } catch (PDOException $e) {
        // Check if it's a "duplicate column" or "table already exists" error
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ⚠ Statement " . ($index + 1) . " skipped (already exists): " . substr($e->getMessage(), 0, 80) . "...\n";
        } else {
            echo "   ✗ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";

// Migration 2: Enhance purchase orders
echo "2. Enhancing purchase orders table...\n";
$migration2 = include __DIR__ . '/../database/migrations/2025_01_28_000001_enhance_purchase_orders.php';

foreach ($migration2 as $index => $sql) {
    if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
        continue;
    }
    
    try {
        $pdo->exec($sql);
        echo "   ✓ Statement " . ($index + 1) . " executed successfully\n";
    } catch (PDOException $e) {
        // Check if it's a "duplicate column" or "table already exists" error
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ⚠ Statement " . ($index + 1) . " skipped (already exists): " . substr($e->getMessage(), 0, 80) . "...\n";
        } else {
            echo "   ✗ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Supplier integration migrations completed!\n";
echo "\nNext steps:\n";
echo "1. Update existing suppliers with appropriate categories (Product Supplier/Service Provider)\n";
echo "2. Create supplier-item associations for commonly ordered items\n";
echo "3. Begin using the enhanced supplier selection in requisitions\n";
echo "4. Start tracking supplier performance after orders are completed\n";

