<?php

namespace App\Repositories;

use PDO;

class RoomTypeRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(): array
    {
        $params = [];
        $sql = 'SELECT * FROM room_types WHERE 1 = 1';
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }
        $sql .= ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $sql = 'SELECT * FROM room_types WHERE id = :id';
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $type = $stmt->fetch();
        return $type ?: null;
    }

    public function update(int $id, array $data): void
    {
        $allowedFields = ['name', 'description', 'max_guests', 'base_rate', 'amenities', 'image'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields, true)) {
                if ($key === 'amenities' && is_array($value)) {
                    $value = json_encode($value);
                }
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value ?: null;
            }
        }

        if (!$sets) {
            return;
        }

        $sql = 'UPDATE room_types SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function create(array $data): int
    {
        $tenantId = \App\Support\Tenant::id();
        $amenities = $data['amenities'] ?? [];
        if (is_array($amenities)) {
            $amenities = json_encode($amenities);
        }

        $sql = 'INSERT INTO room_types (tenant_id, name, description, max_guests, base_rate, amenities, image)
            VALUES (:tenant_id, :name, :description, :max_guests, :base_rate, :amenities, :image)';
        $params = [
            'tenant_id' => $tenantId,
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'max_guests' => (int)($data['max_guests'] ?? 2),
            'base_rate' => (float)($data['base_rate'] ?? 0),
            'amenities' => $amenities,
            'image' => $data['image'] ?? null,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        // Check if room type is in use
        $params = ['id' => $id];
        $sql = '
            SELECT 
                (SELECT COUNT(*) FROM rooms WHERE room_type_id = :id) as rooms_count,
                (SELECT COUNT(*) FROM reservations WHERE room_type_id = :id) as reservations_count
        ';
        $tenantId = \App\Support\Tenant::id();
        if ($tenantId !== null) {
            // Add tenant conditions to the subqueries
            $sql = '
                SELECT 
                    (SELECT COUNT(*) FROM rooms WHERE room_type_id = :id AND tenant_id = :tenant_id) as rooms_count,
                    (SELECT COUNT(*) FROM reservations WHERE room_type_id = :id AND tenant_id = :tenant_id) as reservations_count
            ';
            $params['tenant_id'] = $tenantId;
        }
        
        $checkStmt = $this->db->prepare($sql);
        $checkStmt->execute($params);
        $result = $checkStmt->fetch();
        
        if ($result && ((int)$result['rooms_count'] > 0 || (int)$result['reservations_count'] > 0)) {
            return false; // Cannot delete if in use
        }

        $params = ['id' => $id];
        $sql = 'DELETE FROM room_types WHERE id = :id';
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function replaceAll(array $mapping): void
    {
        // mapping: ['old_type_id' => 'new_type_id']
        $this->db->beginTransaction();
        try {
            $tenantId = \App\Support\Tenant::id();
            
            // Update all rooms
            foreach ($mapping as $oldId => $newId) {
                $params = ['new_id' => $newId, 'old_id' => $oldId];
                $sql = 'UPDATE rooms SET room_type_id = :new_id WHERE room_type_id = :old_id';
                if ($tenantId !== null) {
                    $sql .= ' AND tenant_id = :tenant_id';
                    $params['tenant_id'] = $tenantId;
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            // Update all reservations
            foreach ($mapping as $oldId => $newId) {
                $params = ['new_id' => $newId, 'old_id' => $oldId];
                $sql = 'UPDATE reservations SET room_type_id = :new_id WHERE room_type_id = :old_id';
                if ($tenantId !== null) {
                    $sql .= ' AND tenant_id = :tenant_id';
                    $params['tenant_id'] = $tenantId;
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            // Delete old room types
            $oldIds = array_keys($mapping);
            if (!empty($oldIds)) {
                $placeholders = implode(',', array_fill(0, count($oldIds), '?'));
                $sql = "DELETE FROM room_types WHERE id IN ({$placeholders})";
                if ($tenantId !== null) {
                    $sql .= ' AND tenant_id = ?';
                    $oldIds[] = $tenantId;
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($oldIds);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}


