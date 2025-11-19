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
        $params = [];
        $sql = '
            SELECT pos_categories.id AS category_id,
                   pos_categories.name AS category_name,
                   pos_items.id AS item_id,
                   pos_items.name AS item_name,
                   pos_items.price,
                   pos_items.sku
            FROM pos_categories
            LEFT JOIN pos_items ON pos_items.category_id = pos_categories.id
            WHERE 1 = 1
        ';
        
        
        $sql .= ' ORDER BY pos_categories.name, pos_items.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $categories = [];
        foreach ($rows as $row) {
            $catId = $row['category_id'];
            if (!isset($categories[$catId])) {
                $categories[$catId] = [
                    'id' => $catId,
                    'name' => $row['category_name'],
                    'items' => [],
                ];
            }
            if ($row['item_id']) {
                $categories[$catId]['items'][] = [
                    'id' => $row['item_id'],
                    'name' => $row['item_name'],
                    'price' => $row['price'],
                    'sku' => $row['sku'],
                ];
            }
        }

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
}

