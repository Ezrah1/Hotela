<?php

namespace App\Repositories;

use PDO;

class PosItemRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function categoriesWithItems(): array
    {
        // Get all items with their categories - deduplicated by item ID
        // Only show items that are meant for sale (not inventory-only items)
        $sql = '
            SELECT DISTINCT
                pc.id AS category_id,
                pc.name AS category_name,
                pi.id AS item_id,
                pi.name AS item_name,
                pi.price,
                pi.sku,
                pi.is_inventory_item,
                pi.tracked
            FROM pos_items pi
            INNER JOIN pos_categories pc ON pc.id = pi.category_id
            WHERE (pi.is_inventory_item IS NULL OR pi.is_inventory_item = 0)
            ORDER BY pc.name, pi.name, pi.id
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Group items by category - deduplicate by item ID
        $categories = [];
        $seenItemIds = [];
        $inventoryRepo = new \App\Repositories\InventoryRepository();
        $locations = $inventoryRepo->locations();
        $locationId = !empty($locations) ? (int)$locations[0]['id'] : 0;
        
        foreach ($rows as $row) {
            $itemId = (int)$row['item_id'];
            
            // Skip if we've already seen this item ID (prevent duplicates)
            if (isset($seenItemIds[$itemId])) {
                continue;
            }
            $seenItemIds[$itemId] = true;
            
            // Only include items that are meant for sale
            // Exclude items that are marked as inventory-only (is_inventory_item = 1)
            if (!empty($row['is_inventory_item'])) {
                continue; // Skip inventory-only items
            }
            
            $catId = (int)$row['category_id'];
            
            if (!isset($categories[$catId])) {
                $categories[$catId] = [
                    'id' => $catId,
                    'name' => $row['category_name'],
                    'items' => [],
                ];
            }
            
            // Check availability: items without components or tracking are always available
            // Items with components need to have sufficient stock
            $isAvailable = true;
            if (!empty($row['tracked'])) {
                // Check if item has components and if they're available
                $components = $this->getComponents($itemId);
                if (!empty($components)) {
                    foreach ($components as $component) {
                        $need = (float)($component['quantity_per_sale'] ?? 0);
                        if ($need > 0) {
                            $have = $inventoryRepo->level((int)$component['inventory_item_id'], $locationId);
                            $allowNegative = !empty($component['allow_negative']);
                            if ($have < $need && !$allowNegative) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    }
                }
            }
            
            $categories[$catId]['items'][] = [
                'id' => $itemId,
                'name' => $row['item_name'],
                'price' => (float)$row['price'],
                'sku' => $row['sku'] ?? '',
                'available' => $isAvailable,
            ];
        }

        // Return only categories with items (no empty categories)
        return array_values($categories);
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $sql = 'SELECT * FROM pos_items WHERE id = :id';
        
        
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    /**
     * POS items without component mappings to inventory items.
     */
    public function unmappedItems(int $limit = 20): array
    {
        
        $params = [];
        $sql = '
            SELECT i.id, i.name, i.sku, c.name AS category
            FROM pos_items i
            LEFT JOIN pos_item_components pic ON pic.pos_item_id = i.id
            LEFT JOIN pos_categories c ON c.id = i.category_id
            WHERE pic.id IS NULL
        ';
        
        $sql .= ' ORDER BY i.name LIMIT ' . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function unmappedCount(): int
    {
        
        $params = [];
        $sql = '
            SELECT COUNT(*) FROM pos_items i
            LEFT JOIN pos_item_components pic ON pic.pos_item_id = i.id
            WHERE pic.id IS NULL
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Validate that item IDs exist in the database
     */
    public function validateItems(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        $itemIds = array_unique(array_filter(array_map('intval', $itemIds)));
        if (empty($itemIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "SELECT id FROM pos_items WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($itemIds);
        
        return array_column($stmt->fetchAll(), 'id');
    }
    
    /**
     * Get all POS items with their categories
     */
    public function all(): array
    {
        $sql = '
            SELECT 
                pi.*,
                pc.name AS category_name
            FROM pos_items pi
            INNER JOIN pos_categories pc ON pc.id = pi.category_id
            ORDER BY pc.name, pi.name
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get all POS categories
     */
    public function categories(): array
    {
        $sql = 'SELECT * FROM pos_categories ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new POS item
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO pos_items (category_id, name, price, sku, tracked, is_inventory_item, production_cost)
            VALUES (:category_id, :name, :price, :sku, :tracked, :is_inventory_item, :production_cost)
        ');
        $stmt->execute([
            'category_id' => (int)$data['category_id'],
            'name' => $data['name'],
            'price' => (float)($data['price'] ?? 0),
            'sku' => $data['sku'] ?? null,
            'tracked' => !empty($data['tracked']) ? 1 : 0,
            'is_inventory_item' => !empty($data['is_inventory_item']) ? 1 : 0,
            'production_cost' => (float)($data['production_cost'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update a POS item
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE pos_items 
            SET category_id = :category_id,
                name = :name,
                price = :price,
                sku = :sku,
                tracked = :tracked,
                is_inventory_item = :is_inventory_item,
                production_cost = :production_cost
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'category_id' => (int)$data['category_id'],
            'name' => $data['name'],
            'price' => (float)($data['price'] ?? 0),
            'sku' => $data['sku'] ?? null,
            'tracked' => !empty($data['tracked']) ? 1 : 0,
            'is_inventory_item' => !empty($data['is_inventory_item']) ? 1 : 0,
            'production_cost' => (float)($data['production_cost'] ?? 0),
        ]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete a POS item
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pos_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get components (BOM/recipe) for a POS item
     */
    public function getComponents(int $posItemId): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                pic.*,
                ii.name AS inventory_item_name,
                ii.sku AS inventory_item_sku,
                ii.unit AS inventory_item_unit,
                ii.avg_cost
            FROM pos_item_components pic
            INNER JOIN inventory_items ii ON ii.id = pic.inventory_item_id
            WHERE pic.pos_item_id = :pos_item_id
            ORDER BY ii.name
        ');
        $stmt->execute(['pos_item_id' => $posItemId]);
        return $stmt->fetchAll();
    }
}

