<?php

require __DIR__ . '/../bootstrap/app.php';

$db = db();
$stmt = $db->query('SELECT id, name, price, sku, category_id, is_inventory_item FROM pos_items WHERE name LIKE "%Bottled Water%" OR name LIKE "%Water%" ORDER BY name, id');
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($items) . " items:\n";
foreach ($items as $item) {
    echo sprintf(
        "ID: %d | Name: %s | Price: %s | SKU: %s | Category: %d | Is Inventory: %d\n",
        $item['id'],
        $item['name'],
        $item['price'],
        $item['sku'] ?? 'NULL',
        $item['category_id'],
        $item['is_inventory_item']
    );
}

