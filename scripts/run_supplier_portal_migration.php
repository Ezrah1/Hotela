<?php

/**
 * Run supplier portal migration
 */

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

echo "Running Supplier Portal Migration...\n\n";

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Migration: Add password and portal_enabled to suppliers
if (!columnExists($pdo, 'suppliers', 'password_hash')) {
    try {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
        echo "✓ Added password_hash column\n";
    } catch (PDOException $e) {
        echo "✗ Failed to add password_hash: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ password_hash column already exists\n";
}

if (!columnExists($pdo, 'suppliers', 'portal_enabled')) {
    try {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN portal_enabled TINYINT(1) DEFAULT 1 AFTER password_hash");
        echo "✓ Added portal_enabled column\n";
    } catch (PDOException $e) {
        echo "✗ Failed to add portal_enabled: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ portal_enabled column already exists\n";
}

// Migration: Create supplier_login_codes table
$migration = include __DIR__ . '/../database/migrations/2025_01_28_000002_create_supplier_portal.php';

foreach ($migration as $index => $sql) {
    if (trim($sql) === '' || str_starts_with(trim($sql), '--')) {
        continue;
    }
    
    // Skip ALTER TABLE statements (already handled above)
    if (strpos($sql, 'ALTER TABLE') !== false) {
        continue;
    }
    
    try {
        $pdo->exec($sql);
        echo "✓ Statement " . ($index + 1) . " executed successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Statement " . ($index + 1) . " skipped (already exists)\n";
        } else {
            echo "✗ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Supplier portal migration completed!\n";

