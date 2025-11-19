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
        $user = \App\Support\Auth::user() ?? [];
        $movementStmt->execute([
            'item' => $inventoryItemId,
            'location' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $reference,
            'notes' => $notes,
            'old_qty' => $oldQty,
            'new_qty' => $newQty,
            'user_id' => $user['id'] ?? null,
            'role_key' => $user['role_key'] ?? ($user['role'] ?? null),
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
        
        $sql = '
            SELECT inventory_items.name, inventory_items.sku, inventory_items.reorder_point,
                   inventory_levels.quantity, inventory_locations.name AS location
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
            INNER JOIN inventory_locations ON inventory_locations.id = inventory_levels.location_id
            WHERE inventory_levels.quantity <= inventory_items.reorder_point
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
        
        $sql = '
            SELECT SUM(inventory_levels.quantity * inventory_items.avg_cost) AS total_value
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
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
        $user = \App\Support\Auth::user() ?? [];
        $movementStmt->execute([
            'item' => $inventoryItemId,
            'location' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $reference,
            'notes' => $notes,
            'old_qty' => max(0, $currentQty - $quantity), // approximate prior
            'new_qty' => $currentQty,
            'user_id' => $user['id'] ?? null,
            'role_key' => $user['role_key'] ?? ($user['role'] ?? null),
        ]);
    }

    public function allItems(): array
    {
        
        $sql = 'SELECT id, name, sku, unit FROM inventory_items';
        $params = [];
        
        $sql .= ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
        return $stmt->fetchAll();
    }

    /**
     * Distinct categories for filter UI.
     */
    public function categories(): array
    {
        
        $params = [];
        $sql = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category <> ''";
        
        $sql .= ' ORDER BY category';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_values(array_filter(array_map(fn($r) => $r['category'] ?? null, $rows)));
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
        $sql = "
            SELECT 
                pi.id AS pos_item_id,
                pi.name AS pos_name,
                pi.price AS pos_price,
                pi.sku AS pos_sku,
                pc.name AS pos_category_name,
                ii.id AS inventory_item_id,
                ii.name AS inventory_name,
                ii.category AS inventory_category,
                ii.image,
                COALESCE(SUM(il.quantity), 0) AS stock,
                COALESCE(MIN(ii.allow_negative), 0) AS allow_negative
            FROM pos_items pi
            LEFT JOIN pos_categories pc ON pc.id = pi.category_id
            LEFT JOIN pos_item_components pic ON pic.pos_item_id = pi.id
            LEFT JOIN inventory_items ii ON ii.id = pic.inventory_item_id AND ii.status = 'active'
            LEFT JOIN inventory_levels il ON il.item_id = ii.id
            GROUP BY pi.id, pi.name, pi.price, pi.sku, pc.name, ii.id, ii.name, ii.category, ii.image
            HAVING pos_item_id IS NOT NULL
        ";
        
        $sql .= ' ORDER BY COALESCE(ii.category, pc.name, "General"), pi.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            // Use POS item ID, not inventory item ID
            $posItemId = (int)$row['pos_item_id'];
            if (!$posItemId) {
                continue; // Skip if no POS item ID
            }
            
            // For items without inventory mapping, they're always "in stock"
            $hasInventoryMapping = !empty($row['inventory_item_id']);
            $inStock = $hasInventoryMapping 
                ? (((float)$row['stock'] > 0) || ((int)$row['allow_negative'] === 1))
                : true; // POS items without inventory are always available
            
            if ($hideUnavailable && !$inStock) {
                continue;
            }

            // Map back-of-house categories into front-of-house POS groups
            // Use inventory category if available, otherwise POS category, otherwise "General"
            $rawCat = trim((string)($row['inventory_category'] ?? $row['pos_category_name'] ?? ''));
            if ($rawCat === 'Kitchen') {
                $rawCat = 'Food';
            } elseif ($rawCat === 'Bar') {
                $rawCat = 'Drinks';
            }
            $cat = $rawCat !== '' ? $rawCat : 'General';

            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'name' => $cat,
                    'items' => [],
                ];
            }
            
            // Use POS item details (name, price, sku from pos_items table)
            $itemName = $row['pos_name'];
            $itemPrice = (float)$row['pos_price'];
            $itemSku = $row['pos_sku'] ?? '';
            $itemImage = $row['image'] ?? null;
            
            $grouped[$cat]['items'][] = [
                'id' => $posItemId, // POS item ID, not inventory item ID
                'name' => $itemName,
                'sku' => $itemSku,
                'image' => $itemImage,
                'price' => $itemPrice,
                'in_stock' => $inStock,
                'stock' => (float)$row['stock'],
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
            INSERT INTO inventory_items (name, sku, unit, category, reorder_point, avg_cost, is_pos_item, status, allow_negative, image)
            VALUES (:name, :sku, :unit, :category, :reorder_point, :avg_cost, :is_pos_item, :status, :allow_negative, :image)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'] ?? 'unit',
            'category' => $data['category'] ?? null,
            'reorder_point' => $data['reorder_point'] ?? 0,
            'avg_cost' => $data['avg_cost'] ?? 0,
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

    public function ensurePosComponent(int $posItemId, int $inventoryItemId, float $quantityPerSale = 1.0): void
    {
        // Check if mapping exists
        $check = $this->db->prepare('
            SELECT id FROM pos_item_components WHERE pos_item_id = :pos AND inventory_item_id = :inv LIMIT 1
        ');
        $check->execute(['pos' => $posItemId, 'inv' => $inventoryItemId]);
        $id = $check->fetchColumn();
        if ($id) {
            return;
        }

        $insert = $this->db->prepare('
            INSERT INTO pos_item_components (pos_item_id, inventory_item_id, quantity_per_sale)
            VALUES (:pos, :inv, :qty)
        ');
        $insert->execute([
            'pos' => $posItemId,
            'inv' => $inventoryItemId,
            'qty' => $quantityPerSale,
        ]);
    }
}


