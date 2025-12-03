<?php
/**
 * Diagnostic Script for Auto-Requisition System
 * 
 * This script checks:
 * 1. If required database tables/columns exist
 * 2. If items have reorder points set
 * 3. If there are any errors in the auto-requisition logic
 * 4. Tests the auto-requisition creation
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Repositories\InventoryRepository;
use App\Services\AutoRequisitionService;

$db = db();
echo "=== Auto-Requisition System Diagnostic ===\n\n";

// Check 1: Database tables and columns
echo "1. Checking Database Structure...\n";
echo "--------------------------------\n";

// Check requisitions table has 'type' column
$stmt = $db->query("SHOW COLUMNS FROM requisitions LIKE 'type'");
$hasType = $stmt->fetch();
if ($hasType) {
    echo "✓ requisitions.type column exists\n";
} else {
    echo "❌ requisitions.type column MISSING - Migration may not have run!\n";
    echo "   Run: php scripts/migrate.php\n";
}

// Check requisitions table has 'urgency' column
$stmt = $db->query("SHOW COLUMNS FROM requisitions LIKE 'urgency'");
$hasUrgency = $stmt->fetch();
if ($hasUrgency) {
    echo "✓ requisitions.urgency column exists\n";
} else {
    echo "❌ requisitions.urgency column MISSING\n";
}

// Check auto_requisition_triggers table
$stmt = $db->query("SHOW TABLES LIKE 'auto_requisition_triggers'");
$hasTable = $stmt->fetch();
if ($hasTable) {
    echo "✓ auto_requisition_triggers table exists\n";
} else {
    echo "❌ auto_requisition_triggers table MISSING - Migration may not have run!\n";
    echo "   Run: php scripts/migrate.php\n";
}

// Check inventory_items has minimum_stock column
$stmt = $db->query("SHOW COLUMNS FROM inventory_items LIKE 'minimum_stock'");
$hasMinStock = $stmt->fetch();
if ($hasMinStock) {
    echo "✓ inventory_items.minimum_stock column exists\n";
} else {
    echo "❌ inventory_items.minimum_stock column MISSING\n";
}

echo "\n";

// Check 2: Items with reorder points
echo "2. Checking Items with Reorder Points...\n";
echo "-----------------------------------------\n";

$stmt = $db->query("
    SELECT id, name, sku, reorder_point, minimum_stock 
    FROM inventory_items 
    WHERE reorder_point > 0 OR minimum_stock > 0
    ORDER BY reorder_point DESC
    LIMIT 10
");
$items = $stmt->fetchAll();

if (empty($items)) {
    echo "⚠ No items have reorder points set!\n";
    echo "   Set reorder points in: Inventory → Edit Item → Reorder Point\n";
} else {
    echo "✓ Found " . count($items) . " items with reorder points:\n";
    foreach ($items as $item) {
        $threshold = $item['minimum_stock'] > 0 ? $item['minimum_stock'] : $item['reorder_point'];
        echo "   - {$item['name']} (SKU: {$item['sku']}): Threshold = {$threshold}\n";
    }
}

echo "\n";

// Check 3: Current stock levels
echo "3. Checking Stock Levels for Items with Reorder Points...\n";
echo "----------------------------------------------------------\n";

$inventory = new InventoryRepository();
$locations = $inventory->locations();

if (empty($items)) {
    echo "⚠ Skipping - no items with reorder points\n";
} else {
    foreach ($items as $item) {
        $threshold = $item['minimum_stock'] > 0 ? $item['minimum_stock'] : $item['reorder_point'];
        
        // Get total stock across all locations
        $totalStock = 0;
        $locationBreakdown = [];
        foreach ($locations as $loc) {
            $stock = $inventory->level((int)$item['id'], (int)$loc['id']);
            $totalStock += $stock;
            if ($stock > 0) {
                $locationBreakdown[] = "{$loc['name']}: {$stock}";
            }
        }
        
        $status = $totalStock <= $threshold ? "❌ BELOW THRESHOLD" : "✓ OK";
        echo "{$status} {$item['name']}: Stock = {$totalStock}, Threshold = {$threshold}\n";
        if (!empty($locationBreakdown)) {
            echo "   Locations: " . implode(", ", $locationBreakdown) . "\n";
        }
    }
}

echo "\n";

// Check 4: Existing auto-requisitions
echo "4. Checking Existing Auto-Requisitions...\n";
echo "------------------------------------------\n";

$stmt = $db->query("
    SELECT r.id, r.reference, r.status, r.urgency, r.created_at, 
           ri.inventory_item_id, ri.quantity, ii.name as item_name
    FROM requisitions r
    INNER JOIN requisition_items ri ON ri.requisition_id = r.id
    INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
    WHERE r.type = 'auto'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reqs = $stmt->fetchAll();

if (empty($reqs)) {
    echo "⚠ No auto-requisitions found\n";
} else {
    echo "✓ Found " . count($reqs) . " auto-requisitions:\n";
    foreach ($reqs as $req) {
        echo "   - {$req['reference']}: {$req['item_name']} (Qty: {$req['quantity']}, Status: {$req['status']}, Urgency: {$req['urgency']})\n";
    }
}

echo "\n";

// Check 5: Test auto-requisition creation
echo "5. Testing Auto-Requisition Logic...\n";
echo "-----------------------------------\n";

if (empty($items)) {
    echo "⚠ Skipping - no items with reorder points to test\n";
} else {
    $testItem = $items[0];
    $threshold = $testItem['minimum_stock'] > 0 ? $testItem['minimum_stock'] : $testItem['reorder_point'];
    
    // Get total stock
    $totalStock = 0;
    foreach ($locations as $loc) {
        $totalStock += $inventory->level((int)$testItem['id'], (int)$loc['id']);
    }
    
    echo "Testing with: {$testItem['name']}\n";
    echo "  Current stock: {$totalStock}\n";
    echo "  Threshold: {$threshold}\n";
    
    if ($totalStock <= $threshold) {
        echo "  Status: Should trigger auto-requisition\n";
        
        // Check for existing requisition
        $autoReq = new AutoRequisitionService();
        $existing = $autoReq->checkAndCreateRequisition(
            (int)$testItem['id'], 
            !empty($locations) ? (int)$locations[0]['id'] : 1, 
            $totalStock
        );
        
        if ($existing) {
            echo "  ✓ Auto-requisition created/checked successfully (ID: {$existing})\n";
        } else {
            echo "  ⚠ Auto-requisition check returned null (may already exist or error occurred)\n";
        }
    } else {
        echo "  Status: Stock above threshold - no requisition needed\n";
    }
}

echo "\n";

// Check 6: Error logs
echo "6. Checking for Common Issues...\n";
echo "--------------------------------\n";

// Check if locations exist
if (empty($locations)) {
    echo "❌ No inventory locations found!\n";
    echo "   Create locations in: Inventory → Locations\n";
} else {
    echo "✓ Found " . count($locations) . " inventory locations\n";
}

// Check if any items have stock
$stmt = $db->query("SELECT COUNT(*) as count FROM inventory_levels WHERE quantity > 0");
$hasStock = $stmt->fetch();
if ($hasStock && $hasStock['count'] > 0) {
    echo "✓ Items have stock in inventory_levels\n";
} else {
    echo "⚠ No stock found in inventory_levels table\n";
}

echo "\n";

// Summary
echo "=== Summary ===\n";
echo "If requisitions are not being created, check:\n";
echo "1. Migration has been run (php scripts/migrate.php)\n";
echo "2. Items have reorder points set\n";
echo "3. Stock is actually below threshold\n";
echo "4. No existing pending requisitions for the item\n";
echo "5. Check PHP error logs for 'Auto requisition check failed'\n";
echo "\n";

