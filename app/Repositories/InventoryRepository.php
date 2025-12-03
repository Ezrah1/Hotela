<?php

namespace App\Repositories;

use PDO;

class InventoryRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function deduct(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = '', string $type = 'sale'): void
    {
        

        // Capture old quantity for logging
        $oldQty = $this->level($inventoryItemId, $locationId);

        // Enforce tenant via UPDATE ... JOIN pattern to avoid cross-tenant changes
        $sql = '
            UPDATE inventory_levels il
            INNER JOIN inventory_items ii ON ii.id = il.item_id
            SET il.quantity = il.quantity - :qty
            WHERE il.item_id = :item AND il.location_id = :location
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':qty', $quantity);
        $stmt->bindValue(':item', $inventoryItemId, PDO::PARAM_INT);
        $stmt->bindValue(':location', $locationId, PDO::PARAM_INT);
        
        $stmt->execute();

        $newQty = $this->level($inventoryItemId, $locationId);

        $movementSql = '
            INSERT INTO inventory_movements (item_id, location_id, type, quantity, reference, notes, old_quantity, new_quantity, user_id, role_key)
            VALUES (:item, :location, :type, :quantity, :reference, :notes, :old_qty, :new_qty, :user_id, :role_key)
        ';
        $movementStmt = $this->db->prepare($movementSql);
        
        // Safely get user info - handle cases where user is not authenticated (e.g., guest orders)
        $user = null;
        $userId = null;
        $roleKey = null;
        
        if (\App\Support\Auth::check()) {
            try {
                // Safely get user - handle cases where user is not authenticated
                $user = null;
                if (\App\Support\Auth::check()) {
                    try {
                        $user = \App\Support\Auth::user();
                    } catch (\RuntimeException $e) {
                        $user = null;
                    }
                }
                $userId = $user['id'] ?? null;
                $roleKey = $user['role_key'] ?? ($user['role'] ?? null);
            } catch (\RuntimeException $e) {
                // Not authenticated - use null values
                $userId = null;
                $roleKey = null;
            }
        }
        
        $movementStmt->execute([
            'item' => $inventoryItemId,
            'location' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $reference,
            'notes' => $notes,
            'old_qty' => $oldQty,
            'new_qty' => $newQty,
            'user_id' => $userId,
            'role_key' => $roleKey,
        ]);

        // Check if automatic requisition is needed
        try {
            $autoReqService = new \App\Services\AutoRequisitionService();
            $autoReqService->checkAndCreateRequisition($inventoryItemId, $locationId, $newQty);
        } catch (\Exception $e) {
            // Log error but don't fail the deduction
            error_log('Auto requisition check failed: ' . $e->getMessage());
        }
    }

    public function getBySku(string $sku): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM inventory_items WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $item = $stmt->fetch();

        return $item ?: null;
    }

    public function level(int $itemId, int $locationId): float
    {
        $stmt = $this->db->prepare('SELECT quantity FROM inventory_levels WHERE item_id = :item AND location_id = :location LIMIT 1');
        $stmt->execute(['item' => $itemId, 'location' => $locationId]);
        $qty = $stmt->fetchColumn();

        return (float)$qty;
    }

    public function reorderPoint(int $itemId): float
    {
        
        $sql = 'SELECT reorder_point FROM inventory_items WHERE id = :item';
        $params = ['item' => $itemId];
        
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rp = $stmt->fetchColumn();

        return (float)$rp;
    }

    public function lowStockItems(int $limit = 5): array
    {
        // Only show inventory items (not non-inventory POS items)
        $sql = '
            SELECT inventory_items.name, inventory_items.sku, inventory_items.reorder_point,
                   inventory_levels.quantity, inventory_locations.name AS location
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
            INNER JOIN inventory_locations ON inventory_locations.id = inventory_levels.location_id
            WHERE inventory_levels.quantity <= inventory_items.reorder_point
            AND (inventory_items.status IS NULL OR inventory_items.status = \'active\')
            AND NOT EXISTS (
                SELECT 1 FROM pos_item_components pic
                INNER JOIN pos_items pi ON pi.id = pic.pos_item_id
                WHERE pic.inventory_item_id = inventory_items.id
                AND pi.is_inventory_item = 0
            )
        ';

        $sql .= '
            ORDER BY inventory_levels.quantity ASC
            LIMIT ' . (int)$limit . '
        ';

        $stmt = $this->db->prepare($sql);
        
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function inventoryValuation(): float
    {
        // Only value true inventory items (exclude non-inventory POS items)
        $sql = '
            SELECT SUM(inventory_levels.quantity * inventory_items.avg_cost) AS total_value
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
            WHERE (inventory_items.status IS NULL OR inventory_items.status = \'active\')
            AND NOT EXISTS (
                SELECT 1 FROM pos_item_components pic
                INNER JOIN pos_items pi ON pi.id = pic.pos_item_id
                WHERE pic.inventory_item_id = inventory_items.id
                AND pi.is_inventory_item = 0
            )
        ';
        $params = [];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function addStock(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = '', string $type = 'purchase'): void
    {
        
        $stmt = $this->db->prepare('
            INSERT INTO inventory_levels (item_id, location_id, quantity)
            VALUES (:item, :location, :qty)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ');
        $stmt->execute([
            'qty' => $quantity,
            'item' => $inventoryItemId,
            'location' => $locationId,
        ]);

        $currentQty = $this->level($inventoryItemId, $locationId);
        $movementStmt = $this->db->prepare('
            INSERT INTO inventory_movements (item_id, location_id, type, quantity, reference, notes, old_quantity, new_quantity, user_id, role_key)
            VALUES (:item, :location, :type, :quantity, :reference, :notes, :old_qty, :new_qty, :user_id, :role_key)
        ');
        
        // Safely get user info - handle cases where user is not authenticated (e.g., guest orders)
        $user = null;
        $userId = null;
        $roleKey = null;
        
        if (\App\Support\Auth::check()) {
            try {
                $user = \App\Support\Auth::user();
                $userId = $user['id'] ?? null;
                $roleKey = $user['role_key'] ?? ($user['role'] ?? null);
            } catch (\RuntimeException $e) {
                // Not authenticated - use null values
                $userId = null;
                $roleKey = null;
            }
        }
        
        $movementStmt->execute([
            'item' => $inventoryItemId,
            'location' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $reference,
            'notes' => $notes,
            'old_qty' => max(0, $currentQty - $quantity), // approximate prior
            'new_qty' => $currentQty,
            'user_id' => $userId,
            'role_key' => $roleKey,
        ]);
    }

    public function allItems(): array
    {
        // Only return items that are inventory items (not non-inventory POS items)
        $sql = '
            SELECT ii.id, ii.name, ii.sku, ii.unit 
            FROM inventory_items ii
            WHERE (ii.status IS NULL OR ii.status = \'active\')
            AND ii.name NOT LIKE \'[Category:%\' -- Exclude placeholder category items
            AND NOT EXISTS (
                SELECT 1 FROM pos_item_components pic
                INNER JOIN pos_items pi ON pi.id = pic.pos_item_id
                WHERE pic.inventory_item_id = ii.id
                AND pi.is_inventory_item = 0
            )
            ORDER BY ii.name
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItem(int $itemId): ?array
    {
        
        $sql = 'SELECT * FROM inventory_items WHERE id = :id';
        $params = ['id' => $itemId];
        
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $item = $stmt->fetch();

        return $item ?: null;
    }

    public function getLocationName(int $locationId): ?string
    {
        $stmt = $this->db->prepare('SELECT name FROM inventory_locations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $locationId]);

        $name = $stmt->fetchColumn();
        return $name ?: null;
    }

    public function locations(): array
    {
        
        $sql = 'SELECT * FROM inventory_locations';
        $params = [];
        
        $sql .= ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find the best location for an inventory item (location with highest stock, or first available)
     */
    public function findBestLocationForItem(int $inventoryItemId): ?int
    {
        $sql = '
            SELECT location_id, quantity 
            FROM inventory_levels 
            WHERE item_id = :item_id AND quantity > 0
            ORDER BY quantity DESC
            LIMIT 1
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['item_id' => $inventoryItemId]);
        $result = $stmt->fetch();
        
        if ($result) {
            return (int)$result['location_id'];
        }
        
        // If no location has stock, return the first location (or null if no locations exist)
        $locations = $this->locations();
        return !empty($locations) ? (int)$locations[0]['id'] : null;
    }

    /**
     * Get locations with stock for a specific inventory item
     */
    public function getLocationsWithStock(int $inventoryItemId): array
    {
        $sql = '
            SELECT il.location_id, il.quantity, loc.name AS location_name
            FROM inventory_levels il
            INNER JOIN inventory_locations loc ON loc.id = il.location_id
            WHERE il.item_id = :item_id AND il.quantity > 0
            ORDER BY il.quantity DESC
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['item_id' => $inventoryItemId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns inventory items with aggregated stock across locations, optionally filtered by category.
     * Also includes stock breakdown per location.
     * Only returns items that are marked as inventory items (not non-inventory POS items like tea, coffee).
     */
    public function itemsWithStock(?string $category = null, ?string $search = null): array
    {
        
        $params = [];
        $sql = '
            SELECT ii.id, ii.name, ii.sku, ii.unit, ii.reorder_point, ii.category,
                   COALESCE(SUM(il.quantity), 0) AS stock
            FROM inventory_items ii
            LEFT JOIN inventory_levels il ON il.item_id = ii.id
            WHERE 1=1
            AND (ii.status IS NULL OR ii.status = \'active\')
            AND ii.name NOT LIKE \'[Category:%\' -- Exclude placeholder category items
        ';
        
        // Exclude POS items that are not inventory items
        // Only show items that are either:
        // 1. Not linked to any POS item (pure inventory items), OR
        // 2. Linked to POS items that are marked as inventory items
        $sql .= '
            AND NOT EXISTS (
                SELECT 1 FROM pos_item_components pic
                INNER JOIN pos_items pi ON pi.id = pic.pos_item_id
                WHERE pic.inventory_item_id = ii.id
                AND pi.is_inventory_item = 0
            )
        ';
        
        if ($category !== null && $category !== '') {
            $sql .= ' AND ii.category = :category';
            $params['category'] = $category;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND (ii.name LIKE :q OR ii.sku LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sql .= '
            GROUP BY ii.id, ii.name, ii.sku, ii.unit, ii.reorder_point, ii.category
            ORDER BY ii.name
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Add location breakdown for each item
        foreach ($items as &$item) {
            $item['stock_by_location'] = $this->getLocationsWithStock((int)$item['id']);
        }
        
        return $items;
    }

    /**
     * Check if a value is a department name (not a category)
     */
    protected function isDepartment(string $value): bool
    {
        $departments = ['bar', 'kitchen', 'housekeeping', 'maintenance', 'front desk', 'reception', 'frontdesk'];
        return in_array(strtolower(trim($value)), $departments);
    }

    /**
     * Distinct categories for filter UI.
     * Excludes department names (Bar, Kitchen, etc.) which are not categories.
     */
    public function categories(): array
    {
        $sql = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category <> ''";
        $sql .= ' ORDER BY category';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        // Filter out departments and return only actual categories
        $categories = array_filter(array_map(fn($r) => $r['category'] ?? null, $rows), function($cat) {
            if (!$cat) return false;
            return !$this->isDepartment($cat);
        });
        
        return array_values($categories);
    }

    /**
     * POS-enabled items grouped by category, filtered by flags and stock levels.
     */
    public function posEnabledByCategory(?bool $hideUnavailable = false): array
    {
        
        $params = [];
        // Query to get POS items with their inventory mappings
        // We need POS item IDs, not inventory item IDs
        // This query gets all POS items, whether they have inventory mappings or not
        // First, get all POS items with their basic info (no duplicates)
        $sql = "
            SELECT DISTINCT
                pi.id AS pos_item_id,
                pi.name AS pos_name,
                pi.price AS pos_price,
                pi.sku AS pos_sku,
                pc.name AS pos_category_name
            FROM pos_items pi
            INNER JOIN pos_categories pc ON pc.id = pi.category_id
            ORDER BY pc.name, pi.name, pi.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Get stock information for items with inventory components
        $posItemIds = array_unique(array_column($rows, 'pos_item_id'));
        $stockInfo = [];
        if (!empty($posItemIds)) {
            $placeholders = implode(',', array_fill(0, count($posItemIds), '?'));
            $stockSql = "
                SELECT 
                    pic.pos_item_id,
                    COALESCE(SUM(il.quantity), 0) AS stock,
                    COALESCE(MIN(ii.allow_negative), 0) AS allow_negative
                FROM pos_item_components pic
                INNER JOIN inventory_items ii ON ii.id = pic.inventory_item_id AND (ii.status IS NULL OR ii.status = 'active')
                LEFT JOIN inventory_levels il ON il.item_id = ii.id
                WHERE pic.pos_item_id IN ($placeholders)
                GROUP BY pic.pos_item_id
            ";
            $stockStmt = $this->db->prepare($stockSql);
            $stockStmt->execute($posItemIds);
            $stockRows = $stockStmt->fetchAll();
            foreach ($stockRows as $stockRow) {
                $stockInfo[(int)$stockRow['pos_item_id']] = [
                    'stock' => (float)$stockRow['stock'],
                    'allow_negative' => (int)$stockRow['allow_negative'],
                ];
            }
        }

        $grouped = [];
        $seenItems = []; // Track items we've already added to prevent duplicates
        
        foreach ($rows as $row) {
            $posItemId = (int)$row['pos_item_id'];
            if (!$posItemId) {
                continue;
            }
            
            // Skip if we've already added this exact item (by ID)
            if (isset($seenItems[$posItemId])) {
                continue;
            }
            $seenItems[$posItemId] = true;
            
            // Check stock for items with inventory components
            $hasInventoryMapping = isset($stockInfo[$posItemId]);
            $inStock = $hasInventoryMapping 
                ? (($stockInfo[$posItemId]['stock'] > 0) || ($stockInfo[$posItemId]['allow_negative'] === 1))
                : true; // POS items without inventory are always available
            
            if ($hideUnavailable && !$inStock) {
                continue;
            }

            // Use POS category name
            $cat = trim((string)($row['pos_category_name'] ?? 'General'));
            
            // Ensure we don't use department names as categories
            if (empty($cat) || $this->isDepartment($cat)) {
                $cat = 'General';
            }

            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'name' => $cat,
                    'items' => [],
                ];
            }
            
            $grouped[$cat]['items'][] = [
                'id' => $posItemId,
                'name' => $row['pos_name'],
                'sku' => $row['pos_sku'] ?? '',
                'image' => null, // Image would need separate query if needed
                'price' => (float)$row['pos_price'],
                'in_stock' => $inStock,
                'stock' => $hasInventoryMapping ? $stockInfo[$posItemId]['stock'] : 0,
            ];
        }

        return array_values($grouped);
    }

    public function findBySku(string $sku): ?array
    {
        
        $sql = 'SELECT * FROM inventory_items WHERE sku = :sku';
        $params = ['sku' => $sku];
        
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createItem(array $data): int
    {
        
        $stmt = $this->db->prepare('
            INSERT INTO inventory_items (name, sku, unit, category, reorder_point, avg_cost, selling_price, is_pos_item, status, allow_negative, image)
            VALUES (:name, :sku, :unit, :category, :reorder_point, :avg_cost, :selling_price, :is_pos_item, :status, :allow_negative, :image)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'] ?? 'unit',
            'category' => $data['category'] ?? null,
            'reorder_point' => $data['reorder_point'] ?? 0,
            'avg_cost' => $data['avg_cost'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'is_pos_item' => (int)($data['is_pos_item'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'allow_negative' => (int)($data['allow_negative'] ?? 0),
            'image' => $data['image'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateItem(int $itemId, array $data): bool
    {
        
        $stmt = $this->db->prepare('
            UPDATE inventory_items 
            SET name = :name, 
                sku = :sku, 
                unit = :unit, 
                category = :category, 
                reorder_point = :reorder_point, 
                avg_cost = :avg_cost,
                selling_price = :selling_price,
                is_pos_item = :is_pos_item,
                status = :status,
                allow_negative = :allow_negative,
                image = :image
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $itemId,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'] ?? 'unit',
            'category' => $data['category'] ?? null,
            'reorder_point' => $data['reorder_point'] ?? 0,
            'avg_cost' => $data['avg_cost'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'is_pos_item' => (int)($data['is_pos_item'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'allow_negative' => (int)($data['allow_negative'] ?? 0),
            'image' => $data['image'] ?? null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteItem(int $itemId): bool
    {
        
        $stmt = $this->db->prepare('DELETE FROM inventory_items WHERE id = :id');
        $stmt->execute(['id' => $itemId]);
        return $stmt->rowCount() > 0;
    }

    public function ensurePosComponent(int $posItemId, int $inventoryItemId, float $quantityPerSale = 1.0, ?string $sourceUnit = null, ?string $targetUnit = null, float $conversionFactor = 1.0): void
    {
        // Check if mapping exists
        $check = $this->db->prepare('
            SELECT id FROM pos_item_components WHERE pos_item_id = :pos AND inventory_item_id = :inv LIMIT 1
        ');
        $check->execute(['pos' => $posItemId, 'inv' => $inventoryItemId]);
        $id = $check->fetchColumn();
        if ($id) {
            // Update existing mapping with unit conversion if provided
            if ($sourceUnit !== null || $targetUnit !== null) {
                $update = $this->db->prepare('
                    UPDATE pos_item_components 
                    SET quantity_per_sale = :qty, 
                        source_unit = :source_unit, 
                        target_unit = :target_unit, 
                        conversion_factor = :factor
                    WHERE id = :id
                ');
                $update->execute([
                    'id' => $id,
                    'qty' => $quantityPerSale,
                    'source_unit' => $sourceUnit,
                    'target_unit' => $targetUnit,
                    'factor' => $conversionFactor,
                ]);
                
                // Update production cost after updating component
                $this->updateProductionCost($posItemId);
            }
            return;
        }

        $insert = $this->db->prepare('
            INSERT INTO pos_item_components (pos_item_id, inventory_item_id, quantity_per_sale, source_unit, target_unit, conversion_factor)
            VALUES (:pos, :inv, :qty, :source_unit, :target_unit, :factor)
        ');
        $insert->execute([
            'pos' => $posItemId,
            'inv' => $inventoryItemId,
            'qty' => $quantityPerSale,
            'source_unit' => $sourceUnit,
            'target_unit' => $targetUnit,
            'factor' => $conversionFactor,
        ]);
        
        // Update production cost after adding component
        $this->updateProductionCost($posItemId);
    }

    /**
     * Get all components for a POS item with unit conversion support
     */
    public function getPosComponents(int $posItemId): array
    {
        $stmt = $this->db->prepare('
            SELECT pic.*, ii.unit as inventory_unit, ii.avg_cost
            FROM pos_item_components pic
            INNER JOIN inventory_items ii ON ii.id = pic.inventory_item_id
            WHERE pic.pos_item_id = :pos
        ');
        $stmt->execute(['pos' => $posItemId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate production cost for a POS item based on its BOM/recipe
     * Returns the sum of (ingredient cost * quantity per sale) for all components
     */
    public function calculateProductionCost(int $posItemId): float
    {
        $components = $this->getPosComponents($posItemId);
        $totalCost = 0.0;
        
        foreach ($components as $component) {
            $avgCost = (float)($component['avg_cost'] ?? 0);
            $quantityPerSale = (float)($component['quantity_per_sale'] ?? 0);
            
            // Apply unit conversion if needed
            $sourceUnit = $component['source_unit'] ?? null;
            $targetUnit = $component['target_unit'] ?? $component['inventory_unit'] ?? null;
            $conversionFactor = (float)($component['conversion_factor'] ?? 1.0);
            
            // Convert quantity to inventory unit
            $convertedQty = $this->convertQuantity($quantityPerSale, $sourceUnit, $targetUnit, $conversionFactor);
            
            $totalCost += $avgCost * $convertedQty;
        }
        
        return $totalCost;
    }
    
    /**
     * Update production cost for a POS item
     */
    public function updateProductionCost(int $posItemId): void
    {
        $cost = $this->calculateProductionCost($posItemId);
        $stmt = $this->db->prepare('
            UPDATE pos_items 
            SET production_cost = :cost 
            WHERE id = :id
        ');
        $stmt->execute([
            'cost' => $cost,
            'id' => $posItemId,
        ]);
    }

    /**
     * Convert quantity using unit conversion factor
     */
    public function convertQuantity(float $quantity, ?string $sourceUnit, ?string $targetUnit, float $conversionFactor = 1.0): float
    {
        // If units are the same or no conversion needed, return as-is
        if ($sourceUnit === $targetUnit || $conversionFactor === 1.0 || $sourceUnit === null || $targetUnit === null) {
            return $quantity;
        }
        
        // Apply conversion factor
        return $quantity * $conversionFactor;
    }

    /**
     * Transfer stock from one location to another
     * Creates movement records for both source (deduction) and destination (addition)
     */
    public function transfer(int $inventoryItemId, int $fromLocationId, int $toLocationId, float $quantity, string $reference, string $notes = ''): void
    {
        // Deduct from source location
        $this->deduct($inventoryItemId, $fromLocationId, $quantity, $reference, $notes . ' (Transfer out)', 'transfer');
        
        // Add to destination location
        $this->addStock($inventoryItemId, $toLocationId, $quantity, $reference, $notes . ' (Transfer in)', 'transfer');
    }

    /**
     * Adjust stock level (manual correction)
     * Creates movement record for the adjustment
     */
    public function adjust(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = ''): void
    {
        $oldQty = $this->level($inventoryItemId, $locationId);
        $adjustment = $quantity - $oldQty; // Positive = increase, Negative = decrease
        
        if ($adjustment > 0) {
            // Increase stock
            $this->addStock($inventoryItemId, $locationId, $adjustment, $reference, $notes, 'adjustment');
        } elseif ($adjustment < 0) {
            // Decrease stock
            $this->deduct($inventoryItemId, $locationId, abs($adjustment), $reference, $notes, 'adjustment');
        }
        // If adjustment is 0, no change needed
    }

    /**
     * Record waste/spoilage
     * Creates movement record for waste
     */
    public function recordWaste(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = ''): void
    {
        $this->deduct($inventoryItemId, $locationId, $quantity, $reference, $notes, 'waste');
    }

    /**
     * Recalculate inventory levels from movements (for audit/verification)
     * This ensures inventory_levels matches the sum of all movements
     */
    public function recalculateLevels(int $itemId, int $locationId): float
    {
        $stmt = $this->db->prepare('
            SELECT 
                SUM(CASE WHEN type IN ("purchase", "transfer") THEN quantity ELSE 0 END) -
                SUM(CASE WHEN type IN ("sale", "waste", "adjustment") THEN quantity ELSE 0 END) AS calculated_stock
            FROM inventory_movements
            WHERE item_id = :item AND location_id = :location
        ');
        $stmt->execute(['item' => $itemId, 'location' => $locationId]);
        $calculated = (float)($stmt->fetchColumn() ?? 0);
        
        // Update inventory_levels to match calculated value
        $update = $this->db->prepare('
            INSERT INTO inventory_levels (item_id, location_id, quantity)
            VALUES (:item, :location, :qty)
            ON DUPLICATE KEY UPDATE quantity = :qty
        ');
        $update->execute([
            'item' => $itemId,
            'location' => $locationId,
            'qty' => max(0, $calculated), // Ensure non-negative
        ]);
        
        return max(0, $calculated);
    }

    /**
     * Get low stock alerts formatted for kitchen dashboard
     */
    public function getLowStockAlerts(int $limit = 10): array
    {
        $items = $this->lowStockItems($limit);
        $alerts = [];
        
        foreach ($items as $item) {
            $quantity = (float)($item['quantity'] ?? 0);
            $reorderPoint = (float)($item['reorder_point'] ?? 0);
            $name = $item['name'] ?? 'Unknown Item';
            $location = $item['location'] ?? '';
            
            if ($quantity <= 0) {
                $alerts[] = sprintf('%s is out of stock%s', $name, $location ? ' (' . $location . ')' : '');
            } elseif ($quantity < $reorderPoint) {
                $alerts[] = sprintf('%s is below reorder point (%.2f / %.2f)%s', 
                    $name, $quantity, $reorderPoint, $location ? ' (' . $location . ')' : '');
            }
        }
        
        return $alerts;
    }
}


