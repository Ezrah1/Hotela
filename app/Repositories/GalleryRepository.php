<?php

namespace App\Repositories;

use PDO;

class GalleryRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Get all published gallery items, ordered by display_order
     */
    public function allPublished(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM gallery_items 
            WHERE status = "published"
            ORDER BY display_order ASC, created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all gallery items (for admin)
     */
    public function all(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM gallery_items 
            ORDER BY display_order ASC, created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find gallery item by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM gallery_items 
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Create a new gallery item
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO gallery_items (title, description, image_url, display_order, status)
            VALUES (:title, :description, :image_url, :display_order, :status)
        ');
        $stmt->execute([
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'image_url' => $data['image_url'] ?? '',
            'display_order' => $data['display_order'] ?? 0,
            'status' => $data['status'] ?? 'published',
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update a gallery item
     */
    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'description', 'image_url', 'display_order', 'status'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = 'updated_at = NOW()';
        $sql = 'UPDATE gallery_items SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a gallery item
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM gallery_items WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update display order for multiple items
     */
    public function updateOrder(array $orders): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE gallery_items SET display_order = :order WHERE id = :id');
            foreach ($orders as $id => $order) {
                $stmt->execute(['id' => (int)$id, 'order' => (int)$order]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

