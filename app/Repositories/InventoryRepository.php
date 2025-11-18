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
        $tenantId = \App\Support\Tenant::id();

        // Capture old quantity for logging
        $oldQty = $this->level($inventoryItemId, $locationId);

        // Enforce tenant via UPDATE ... JOIN pattern to avoid cross-tenant changes
        $sql = '
            UPDATE inventory_levels il
            INNER JOIN inventory_items ii ON ii.id = il.item_id
            SET il.quantity = il.quantity - :qty
            WHERE il.item_id = :item AND il.location_id = :location
        ';
        if ($tenantId !== null) {
            $sql .= ' AND ii.tenant_id = :tenant';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':qty', $quantity);
        $stmt->bindValue(':item', $inventoryItemId, PDO::PARAM_INT);
        $stmt->bindValue(':location', $locationId, PDO::PARAM_INT);
        if ($tenantId !== null) {
            $stmt->bindValue(':tenant', $tenantId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $newQty = $this->level($inventoryItemId, $locationId);

        $movementSql = '
            INSERT INTO inventory_movements (tenant_id, item_id, location_id, type, quantity, reference, notes, old_quantity, new_quantity, user_id, role_key)
            VALUES (:tenant, :item, :location, :type, :quantity, :reference, :notes, :old_qty, :new_qty, :user_id, :role_key)
        ';
        $movementStmt = $this->db->prepare($movementSql);
        $user = \App\Support\Auth::user() ?? [];
        $movementStmt->execute([
            'tenant' => $tenantId,
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
        $tenantId = \App\Support\Tenant::id();
        $sql = 'SELECT reorder_point FROM inventory_items WHERE id = :item';
        $params = ['item' => $itemId];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rp = $stmt->fetchColumn();

        return (float)$rp;
    }

    public function lowStockItems(int $limit = 5): array
    {
        $tenantId = \App\Support\Tenant::id();
        $sql = '
            SELECT inventory_items.name, inventory_items.sku, inventory_items.reorder_point,
                   inventory_levels.quantity, inventory_locations.name AS location
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
            INNER JOIN inventory_locations ON inventory_locations.id = inventory_levels.location_id
            WHERE inventory_levels.quantity <= inventory_items.reorder_point
        ';

        if ($tenantId !== null) {
            $sql .= ' AND inventory_items.tenant_id = :tenant';
        }

        $sql .= '
            ORDER BY inventory_levels.quantity ASC
            LIMIT ' . (int)$limit . '
        ';

        $stmt = $this->db->prepare($sql);
        if ($tenantId !== null) {
            $stmt->bindValue(':tenant', $tenantId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function inventoryValuation(): float
    {
        $tenantId = \App\Support\Tenant::id();
        $sql = '
            SELECT SUM(inventory_levels.quantity * inventory_items.avg_cost) AS total_value
            FROM inventory_levels
            INNER JOIN inventory_items ON inventory_items.id = inventory_levels.item_id
        ';
        $params = [];

        if ($tenantId !== null) {
            $sql .= ' WHERE inventory_items.tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function addStock(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = '', string $type = 'purchase'): void
    {
        $tenantId = \App\Support\Tenant::id();
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
            INSERT INTO inventory_movements (tenant_id, item_id, location_id, type, quantity, reference, notes, old_quantity, new_quantity, user_id, role_key)
            VALUES (:tenant, :item, :location, :type, :quantity, :reference, :notes, :old_qty, :new_qty, :user_id, :role_key)
        ');
        $user = \App\Support\Auth::user() ?? [];
        $movementStmt->execute([
            'tenant' => $tenantId,
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
        $tenantId = \App\Support\Tenant::id();
        $sql = 'SELECT id, name, sku, unit FROM inventory_items';
        $params = [];
        if ($tenantId !== null) {
            $sql .= ' WHERE tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
        $sql .= ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getItem(int $itemId): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $sql = 'SELECT * FROM inventory_items WHERE id = :id';
        $params = ['id' => $itemId];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
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
        $tenantId = \App\Support\Tenant::id();
        $sql = 'SELECT * FROM inventory_locations';
        $params = [];
        if ($tenantId !== null) {
            $sql .= ' WHERE tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
        $sql .= ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Returns inventory items with aggregated stock across locations, optionally filtered by category.
     */
    public function itemsWithStock(?string $category = null, ?string $search = null): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];
        $sql = '
            SELECT ii.id, ii.name, ii.sku, ii.unit, ii.reorder_point, ii.category,
                   COALESCE(SUM(il.quantity), 0) AS stock
            FROM inventory_items ii
            LEFT JOIN inventory_levels il ON il.item_id = ii.id
            WHERE 1=1
        ';
        if ($tenantId !== null) {
            $sql .= ' AND ii.tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
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
        $tenantId = \App\Support\Tenant::id();
        $params = [];
        $sql = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category <> ''";
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
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
        $tenantId = \App\Support\Tenant::id();
        $params = [];
        $sql = "
            SELECT ii.id, ii.name, ii.sku, ii.category, ii.image,
                   COALESCE(SUM(il.quantity), 0) AS stock,
                   COALESCE(MIN(ii.allow_negative), 0) AS allow_negative,
                   COALESCE(MAX(pi.price), 0) AS price
            FROM inventory_items ii
            LEFT JOIN inventory_levels il ON il.item_id = ii.id
            LEFT JOIN pos_item_components pic ON pic.inventory_item_id = ii.id
            LEFT JOIN pos_items pi ON pi.id = pic.pos_item_id
            WHERE ii.status = 'active' AND (ii.is_pos_item = 1 OR pic.id IS NOT NULL)
        ";
        if ($tenantId !== null) {
            $sql .= ' AND ii.tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
        $sql .= '
            GROUP BY ii.id, ii.name, ii.sku, ii.category, ii.image
            ORDER BY ii.category, ii.name
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $inStock = ((float)$row['stock'] > 0) || ((int)$row['allow_negative'] === 1);
            if ($hideUnavailable && !$inStock) {
                continue;
            }

            // Map back-of-house categories into front-of-house POS groups
            $rawCat = trim((string)($row['category'] ?? ''));
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
            $grouped[$cat]['items'][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'image' => $row['image'],
                'price' => (float)$row['price'],
                'in_stock' => $inStock,
                'stock' => (float)$row['stock'],
            ];
        }

        return array_values($grouped);
    }

    public function findBySku(string $sku): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $sql = 'SELECT * FROM inventory_items WHERE sku = :sku';
        $params = ['sku' => $sku];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant';
            $params['tenant'] = $tenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createItem(array $data): int
    {
        $tenantId = \App\Support\Tenant::id();
        $stmt = $this->db->prepare('
            INSERT INTO inventory_items (tenant_id, name, sku, unit, category, reorder_point, avg_cost, is_pos_item, status, allow_negative, image)
            VALUES (:tenant, :name, :sku, :unit, :category, :reorder_point, :avg_cost, :is_pos_item, :status, :allow_negative, :image)
        ');
        $stmt->execute([
            'tenant' => $tenantId,
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


