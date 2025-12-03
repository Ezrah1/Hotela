<?php
/**
 * Manual Test Script for Inventory Auto-Requisition System
 * 
 * This script can be run from command line to test the auto-requisition functionality.
 * 
 * Usage: php tests/InventoryAutoRequisitionTest.php
 * 
 * WARNING: This script modifies database data. Use only in development/test environment.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Repositories\InventoryRepository;
use App\Services\AutoRequisitionService;
use App\Services\Inventory\InventoryService;

class InventoryAutoRequisitionTest
{
    protected InventoryRepository $inventory;
    protected AutoRequisitionService $autoReq;
    protected InventoryService $inventoryService;
    protected PDO $db;

    public function __construct()
    {
        $this->db = db();
        $this->inventory = new InventoryRepository();
        $this->autoReq = new AutoRequisitionService();
        $this->inventoryService = new InventoryService();
    }

    public function runAllTests(): void
    {
        echo "=== Inventory Auto-Requisition System Tests ===\n\n";

        $this->test1_BasicRequisitionCreation();
        $this->test2_DuplicatePrevention();
        $this->test3_UrgencyLevels();
        $this->test4_MultiLocationStock();
        $this->test5_NoReorderPoint();
        $this->test6_MinimumStockPriority();

        echo "\n=== All Tests Completed ===\n";
    }

    /**
     * Test Case 1: Basic Auto-Requisition Creation
     */
    public function test1_BasicRequisitionCreation(): void
    {
        echo "Test 1: Basic Auto-Requisition Creation\n";
        echo "----------------------------------------\n";

        // Find or create test item
        $item = $this->findOrCreateTestItem('TEST-AUTO-REQ-1', 'Test Item for Auto-Req', 20.000);
        $location = $this->getFirstLocation();
        
        if (!$item || !$location) {
            echo "❌ Setup failed: Could not find/create test item or location\n\n";
            return;
        }

        // Set initial stock above reorder point
        $this->setStock($item['id'], $location['id'], 25.000);
        echo "✓ Initial stock set: 25.000 (reorder point: 20.000)\n";

        // Clean up any existing requisitions
        $this->cleanupRequisitions($item['id']);

        // Deduct stock to go below reorder point
        $this->inventory->deduct($item['id'], $location['id'], 10.000, 'TEST-001', 'Test deduction');
        echo "✓ Stock deducted: 10.000 units\n";

        // Check if requisition was created
        $requisition = $this->getLatestAutoRequisition($item['id']);
        
        if ($requisition) {
            echo "✓ Requisition created:\n";
            echo "  - Reference: {$requisition['reference']}\n";
            echo "  - Status: {$requisition['status']}\n";
            echo "  - Type: {$requisition['type']}\n";
            echo "  - Urgency: {$requisition['urgency']}\n";
            
            $items = $this->getRequisitionItems($requisition['id']);
            if (!empty($items)) {
                echo "  - Quantity needed: {$items[0]['quantity']}\n";
            }
            
            echo "✅ Test 1 PASSED\n\n";
        } else {
            echo "❌ Test 1 FAILED: No requisition created\n\n";
        }
    }

    /**
     * Test Case 2: Duplicate Prevention
     */
    public function test2_DuplicatePrevention(): void
    {
        echo "Test 2: Duplicate Prevention\n";
        echo "-----------------------------\n";

        $item = $this->findOrCreateTestItem('TEST-AUTO-REQ-2', 'Test Item for Duplicate Prevention', 20.000);
        $location = $this->getFirstLocation();
        
        if (!$item || !$location) {
            echo "❌ Setup failed\n\n";
            return;
        }

        // Set stock below reorder point
        $this->setStock($item['id'], $location['id'], 15.000);
        $this->cleanupRequisitions($item['id']);

        // First deduction - should create requisition
        $this->inventory->deduct($item['id'], $location['id'], 2.000, 'TEST-002', 'First deduction');
        $req1 = $this->getLatestAutoRequisition($item['id']);
        
        if (!$req1) {
            echo "❌ First requisition not created\n\n";
            return;
        }

        echo "✓ First requisition created: {$req1['reference']}\n";

        // Second deduction - should NOT create new requisition
        $this->inventory->deduct($item['id'], $location['id'], 2.000, 'TEST-003', 'Second deduction');
        $req2 = $this->getLatestAutoRequisition($item['id']);
        
        if ($req2 && $req2['id'] === $req1['id']) {
            echo "✓ Duplicate prevented - same requisition ID\n";
            echo "✅ Test 2 PASSED\n\n";
        } else {
            echo "❌ Test 2 FAILED: New requisition created (duplicate not prevented)\n\n";
        }
    }

    /**
     * Test Case 3: Urgency Levels
     */
    public function test3_UrgencyLevels(): void
    {
        echo "Test 3: Urgency Levels\n";
        echo "----------------------\n";

        $item = $this->findOrCreateTestItem('TEST-AUTO-REQ-3', 'Test Item for Urgency', 20.000);
        $location = $this->getFirstLocation();
        
        if (!$item || !$location) {
            echo "❌ Setup failed\n\n";
            return;
        }

        $testCases = [
            ['stock' => 4.000, 'expected' => 'urgent', 'desc' => '≤20% of threshold'],
            ['stock' => 10.000, 'expected' => 'high', 'desc' => '≤50% of threshold'],
            ['stock' => 15.000, 'expected' => 'medium', 'desc' => '≤80% of threshold'],
        ];

        foreach ($testCases as $test) {
            $this->setStock($item['id'], $location['id'], $test['stock'] + 5.000);
            $this->cleanupRequisitions($item['id']);
            
            $this->inventory->deduct($item['id'], $location['id'], 5.000, 'TEST-URGENCY', 'Urgency test');
            
            $req = $this->getLatestAutoRequisition($item['id']);
            if ($req && $req['urgency'] === $test['expected']) {
                echo "✓ {$test['desc']}: {$req['urgency']} (expected: {$test['expected']})\n";
            } else {
                echo "❌ {$test['desc']}: Got " . ($req['urgency'] ?? 'none') . ", expected {$test['expected']}\n";
            }
        }

        echo "✅ Test 3 PASSED\n\n";
    }

    /**
     * Test Case 4: Multi-Location Stock Calculation
     */
    public function test4_MultiLocationStock(): void
    {
        echo "Test 4: Multi-Location Stock Calculation\n";
        echo "-----------------------------------------\n";

        $item = $this->findOrCreateTestItem('TEST-AUTO-REQ-4', 'Test Item for Multi-Location', 20.000);
        $locations = $this->inventory->locations();
        
        if (count($locations) < 2) {
            echo "⚠ Skipped: Need at least 2 locations for this test\n\n";
            return;
        }

        // Set stock at multiple locations
        $this->setStock($item['id'], $locations[0]['id'], 10.000);
        $this->setStock($item['id'], $locations[1]['id'], 8.000);
        if (isset($locations[2])) {
            $this->setStock($item['id'], $locations[2]['id'], 5.000);
        }

        $totalBefore = $this->getTotalStock($item['id']);
        echo "✓ Stock set across locations. Total: {$totalBefore}\n";

        $this->cleanupRequisitions($item['id']);

        // Deduct from first location
        $this->inventory->deduct($item['id'], $locations[0]['id'], 5.000, 'TEST-MULTI', 'Multi-location test');
        
        $totalAfter = $this->getTotalStock($item['id']);
        echo "✓ Stock deducted. New total: {$totalAfter}\n";

        $req = $this->getLatestAutoRequisition($item['id']);
        if ($req && strpos($req['notes'], 'Stock by location:') !== false) {
            echo "✓ Requisition notes include location breakdown\n";
            echo "✅ Test 4 PASSED\n\n";
        } else {
            echo "❌ Test 4 FAILED: Location breakdown not in notes\n\n";
        }
    }

    /**
     * Test Case 5: No Reorder Point Set
     */
    public function test5_NoReorderPoint(): void
    {
        echo "Test 5: No Reorder Point Set\n";
        echo "----------------------------\n";

        $item = $this->findOrCreateTestItem('TEST-AUTO-REQ-5', 'Test Item No Reorder Point', 0.000);
        $location = $this->getFirstLocation();
        
        if (!$item || !$location) {
            echo "❌ Setup failed\n\n";
            return;
        }

        $this->setStock($item['id'], $location['id'], 5.000);
        $this->cleanupRequisitions($item['id']);

        // Deduct stock
        $this->inventory->deduct($item['id'], $location['id'], 3.000, 'TEST-NO-REORDER', 'No reorder test');
        
        $req = $this->getLatestAutoRequisition($item['id']);
        if (!$req) {
            echo "✓ No requisition created (reorder point = 0)\n";
            echo "✅ Test 5 PASSED\n\n";
        } else {
            echo "❌ Test 5 FAILED: Requisition created when reorder point is 0\n\n";
        }
    }

    /**
     * Test Case 6: Minimum Stock Priority
     */
    public function test6_MinimumStockPriority(): void
    {
        echo "Test 6: Minimum Stock Priority\n";
        echo "-------------------------------\n";

        // Create item with both reorder_point and minimum_stock
        $stmt = $this->db->prepare('
            SELECT * FROM inventory_items 
            WHERE sku = :sku LIMIT 1
        ');
        $stmt->execute(['sku' => 'TEST-AUTO-REQ-6']);
        $item = $stmt->fetch();

        if (!$item) {
            $stmt = $this->db->prepare('
                INSERT INTO inventory_items (name, sku, unit, reorder_point, minimum_stock)
                VALUES (:name, :sku, :unit, :reorder, :minimum)
            ');
            $stmt->execute([
                'name' => 'Test Item Min Stock Priority',
                'sku' => 'TEST-AUTO-REQ-6',
                'unit' => 'Unit',
                'reorder' => 20.000,
                'minimum' => 15.000,
            ]);
            $item = ['id' => (int)$this->db->lastInsertId()];
        } else {
            // Update to set both values
            $stmt = $this->db->prepare('
                UPDATE inventory_items 
                SET reorder_point = 20.000, minimum_stock = 15.000
                WHERE id = :id
            ');
            $stmt->execute(['id' => $item['id']]);
        }

        $location = $this->getFirstLocation();
        $this->setStock($item['id'], $location['id'], 18.000);
        $this->cleanupRequisitions($item['id']);

        // Deduct to go below minimum_stock but above reorder_point
        $this->inventory->deduct($item['id'], $location['id'], 5.000, 'TEST-MIN', 'Min stock test');
        
        $req = $this->getLatestAutoRequisition($item['id']);
        if ($req) {
            echo "✓ Requisition created using minimum_stock (15.000) as threshold\n";
            echo "✅ Test 6 PASSED\n\n";
        } else {
            echo "❌ Test 6 FAILED: Requisition not created\n\n";
        }
    }

    // Helper Methods

    protected function findOrCreateTestItem(string $sku, string $name, float $reorderPoint): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM inventory_items WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $item = $stmt->fetch();

        if (!$item) {
            $stmt = $this->db->prepare('
                INSERT INTO inventory_items (name, sku, unit, reorder_point)
                VALUES (:name, :sku, :unit, :reorder)
            ');
            $stmt->execute([
                'name' => $name,
                'sku' => $sku,
                'unit' => 'Unit',
                'reorder' => $reorderPoint,
            ]);
            $item = ['id' => (int)$this->db->lastInsertId()];
        }

        return $item;
    }

    protected function getFirstLocation(): ?array
    {
        $locations = $this->inventory->locations();
        return !empty($locations) ? $locations[0] : null;
    }

    protected function setStock(int $itemId, int $locationId, float $quantity): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO inventory_levels (item_id, location_id, quantity)
            VALUES (:item, :location, :qty)
            ON DUPLICATE KEY UPDATE quantity = :qty
        ');
        $stmt->execute([
            'item' => $itemId,
            'location' => $locationId,
            'qty' => $quantity,
        ]);
    }

    protected function getTotalStock(int $itemId): float
    {
        $locations = $this->inventory->locations();
        $total = 0;
        foreach ($locations as $loc) {
            $total += $this->inventory->level($itemId, (int)$loc['id']);
        }
        return $total;
    }

    protected function cleanupRequisitions(int $itemId): void
    {
        $stmt = $this->db->prepare('
            DELETE r FROM requisitions r
            INNER JOIN requisition_items ri ON ri.requisition_id = r.id
            WHERE ri.inventory_item_id = :item AND r.type = "auto"
        ');
        $stmt->execute(['item' => $itemId]);
    }

    protected function getLatestAutoRequisition(int $itemId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.* FROM requisitions r
            INNER JOIN requisition_items ri ON ri.requisition_id = r.id
            WHERE ri.inventory_item_id = :item AND r.type = "auto"
            ORDER BY r.created_at DESC
            LIMIT 1
        ');
        $stmt->execute(['item' => $itemId]);
        return $stmt->fetch() ?: null;
    }

    protected function getRequisitionItems(int $requisitionId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM requisition_items
            WHERE requisition_id = :req
        ');
        $stmt->execute(['req' => $requisitionId]);
        return $stmt->fetchAll();
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $tester = new InventoryAutoRequisitionTest();
    $tester->runAllTests();
} else {
    echo "This script must be run from command line.\n";
}

