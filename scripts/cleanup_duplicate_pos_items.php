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

echo "Found " . count($duplicates) . " groups of duplicate items.\n";
echo "This script will keep the first (lowest ID) and delete the rest.\n\n";

$totalDeleted = 0;

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup['ids']);
    $keepId = (int)$ids[0]; // Keep the first one
    $deleteIds = array_slice($ids, 1); // Delete the rest
    
    echo sprintf(
        "Processing: %s (Price: %s) - Keeping ID %d, Deleting IDs: %s\n",
        $dup['name'],
        $dup['price'],
        $keepId,
        implode(', ', $deleteIds)
    );
    
    // Check if any of the items to delete have sales
    foreach ($deleteIds as $deleteId) {
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM pos_sale_items WHERE item_id = ?');
        $checkStmt->execute([$deleteId]);
        $saleCount = (int)$checkStmt->fetchColumn();
        
        if ($saleCount > 0) {
            echo "  WARNING: Item ID $deleteId has $saleCount sales. Updating sales to use ID $keepId instead of deleting.\n";
            
            // Update sales to point to the kept item
            $updateStmt = $db->prepare('UPDATE pos_sale_items SET item_id = ? WHERE item_id = ?');
            $updateStmt->execute([$keepId, $deleteId]);
            $updatedCount = $updateStmt->rowCount();
            echo "  Updated $updatedCount sale items to use ID $keepId\n";
        }
        
        // Delete the duplicate item (cascade will handle components)
        $deleteStmt = $db->prepare('DELETE FROM pos_items WHERE id = ?');
        $deleteStmt->execute([$deleteId]);
        $totalDeleted += $deleteStmt->rowCount();
    }
    
    echo "\n";
}

echo "Cleanup complete! Deleted $totalDeleted duplicate items.\n";

