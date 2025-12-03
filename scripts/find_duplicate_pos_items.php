<?php

require __DIR__ . '/../bootstrap/app.php';

$db = db();

// Find duplicate POS items (same name, price, category)
$sql = "
    SELECT 
        name, price, category_id, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
    FROM pos_items
    GROUP BY name, price, category_id
    HAVING count > 1
    ORDER BY count DESC, name
";

$stmt = $db->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($duplicates) . " groups of duplicate items:\n\n";

foreach ($duplicates as $dup) {
    echo sprintf(
        "Name: %s | Price: %s | Category ID: %d | Count: %d | IDs: %s\n",
        $dup['name'],
        $dup['price'],
        $dup['category_id'],
        $dup['count'],
        $dup['ids']
    );
    
    // Get details of each duplicate
    $ids = explode(',', $dup['ids']);
    foreach ($ids as $id) {
        $itemStmt = $db->prepare('SELECT id, name, sku, price, category_id, created_at FROM pos_items WHERE id = ?');
        $itemStmt->execute([$id]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
        echo "  - ID: {$item['id']} | SKU: " . ($item['sku'] ?? 'NULL') . " | Created: {$item['created_at']}\n";
    }
    echo "\n";
}

