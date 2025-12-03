<?php

require __DIR__ . '/../bootstrap/app.php';

$migrations = require __DIR__ . '/../database/migrations/2025_01_30_000001_add_is_inventory_item_to_pos_items.php';
$db = db();

foreach ($migrations as $sql) {
    try {
        $db->exec($sql);
        echo "Migration executed successfully: " . substr($sql, 0, 80) . "...\n";
    } catch (Exception $e) {
        echo "Migration error: " . $e->getMessage() . "\n";
        echo "SQL: " . substr($sql, 0, 100) . "...\n";
    }
}

echo "Migration completed.\n";

