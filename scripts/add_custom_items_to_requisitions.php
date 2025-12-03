<?php

require_once __DIR__ . '/../bootstrap/app.php';

$db = db();

try {
    // Check if column already exists
    $checkStmt = $db->query("SHOW COLUMNS FROM requisition_items LIKE 'custom_item_name'");
    if ($checkStmt->rowCount() > 0) {
        echo "Column 'custom_item_name' already exists.\n";
        exit(0);
    }
    
    // Make inventory_item_id nullable
    $db->exec("ALTER TABLE requisition_items MODIFY COLUMN inventory_item_id INT NULL");
    echo "Made inventory_item_id nullable.\n";
    
    // Add custom_item_name column
    $db->exec("ALTER TABLE requisition_items ADD COLUMN custom_item_name VARCHAR(255) NULL AFTER inventory_item_id");
    echo "Added custom_item_name column.\n";
    
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

