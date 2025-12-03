<?php

/**
 * Fix supplier table columns - add missing columns if they don't exist
 */

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Checking and adding missing supplier columns...\n\n";

// Function to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check if index exists
function indexExists($pdo, $table, $index) {
    try {
        $stmt = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

$columns = [
    'category' => "ENUM('product_supplier', 'service_provider', 'both') DEFAULT 'product_supplier'",
    'supplier_group' => "VARCHAR(100) NULL",
    'reliability_score' => "DECIMAL(5,2) DEFAULT 0.00",
    'average_delivery_days' => "INT NULL",
    'last_order_date' => "DATE NULL",
];

// Check and add columns
foreach ($columns as $column => $definition) {
    if (!columnExists($pdo, 'suppliers', $column)) {
        try {
            // Determine position based on column
            $after = 'status';
            if ($column === 'category') {
                $after = 'status';
            } elseif ($column === 'supplier_group') {
                $after = 'category';
            } elseif ($column === 'reliability_score') {
                $after = 'supplier_group';
            } elseif ($column === 'average_delivery_days') {
                $after = 'reliability_score';
            } elseif ($column === 'last_order_date') {
                $after = 'average_delivery_days';
            }
            
            // Check if 'after' column exists, if not use a different position
            if (!columnExists($pdo, 'suppliers', $after) && $after !== 'status') {
                $after = 'status';
            }
            
            $sql = "ALTER TABLE suppliers ADD COLUMN `{$column}` {$definition} AFTER `{$after}`";
            $pdo->exec($sql);
            echo "✓ Added column: {$column}\n";
        } catch (PDOException $e) {
            // If AFTER fails, try without AFTER
            try {
                $sql = "ALTER TABLE suppliers ADD COLUMN `{$column}` {$definition}";
                $pdo->exec($sql);
                echo "✓ Added column: {$column} (without AFTER clause)\n";
            } catch (PDOException $e2) {
                echo "✗ Failed to add column {$column}: " . $e2->getMessage() . "\n";
            }
        }
    } else {
        echo "⚠ Column {$column} already exists, skipping...\n";
    }
}

// Update status enum if needed
try {
    // Check current status column type
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers WHERE Field = 'status'");
    $statusCol = $stmt->fetch();
    
    if ($statusCol && strpos($statusCol['Type'], 'suspended') === false) {
        // Status enum doesn't include new values, need to modify it
        echo "\nUpdating status enum...\n";
        $pdo->exec("ALTER TABLE suppliers MODIFY COLUMN status ENUM('active', 'suspended', 'blacklisted', 'inactive') DEFAULT 'active'");
        echo "✓ Updated status enum\n";
    } else {
        echo "✓ Status enum already updated\n";
    }
} catch (PDOException $e) {
    echo "⚠ Could not update status enum: " . $e->getMessage() . "\n";
}

// Add indexes
$indexes = [
    'idx_suppliers_category' => 'category',
    'idx_suppliers_group' => 'supplier_group',
    'idx_suppliers_status' => 'status',
];

foreach ($indexes as $indexName => $column) {
    if (!indexExists($pdo, 'suppliers', $indexName)) {
        if (columnExists($pdo, 'suppliers', $column)) {
            try {
                $pdo->exec("ALTER TABLE suppliers ADD INDEX `{$indexName}` (`{$column}`)");
                echo "✓ Added index: {$indexName}\n";
            } catch (PDOException $e) {
                echo "✗ Failed to add index {$indexName}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠ Cannot add index {$indexName}: column {$column} does not exist\n";
        }
    } else {
        echo "⚠ Index {$indexName} already exists, skipping...\n";
    }
}

echo "\n✅ Supplier columns check completed!\n";

