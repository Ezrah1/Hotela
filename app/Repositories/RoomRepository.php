<?php

namespace App\Repositories;

use PDO;

class RoomRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    protected function tenantCondition(string $alias, array &$params): string
    {
        // Single installation - no tenant filtering needed
        return '';
    }

    public function allAvailableBetween(string $startDate, string $endDate): array
    {
        $params = ['start' => $startDate, 'end' => $endDate];

        $sql = "
            SELECT rooms.*, room_types.name AS room_type_name
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE rooms.status = 'available'
            AND rooms.id NOT IN (
                SELECT room_id FROM reservations
                WHERE room_id IS NOT NULL
                AND NOT (check_out <= :start OR check_in >= :end)
            )
            ORDER BY room_types.name, rooms.room_number
        ";
        $sql .= $this->tenantCondition('rooms', $params);
        $subCondition = $this->tenantCondition('reservations', $params);
        if ($subCondition) {
            $sql = str_replace(
                'WHERE room_id IS NOT NULL',
                'WHERE room_id IS NOT NULL' . $subCondition,
                $sql
            );
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        // Try to add room_type_image if column exists
        try {
            $checkStmt = $this->db->query('SELECT image FROM room_types LIMIT 1');
            $checkStmt->fetch();
            // Column exists, fetch with image
            $sqlWithImage = "
                SELECT rooms.*, room_types.name AS room_type_name, room_types.image AS room_type_image
                FROM rooms
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE rooms.status = 'available'
                AND rooms.id NOT IN (
                    SELECT room_id FROM reservations
                    WHERE room_id IS NOT NULL
                    AND NOT (check_out <= :start OR check_in >= :end)
                )
                ORDER BY room_types.name, rooms.room_number
            ";
            $sqlWithImage .= $this->tenantCondition('rooms', $params);
            $subCondition = $this->tenantCondition('reservations', $params);
            if ($subCondition) {
                $sqlWithImage = str_replace(
                    'WHERE room_id IS NOT NULL',
                    'WHERE room_id IS NOT NULL' . $subCondition,
                    $sqlWithImage
                );
            }
            $stmtWithImage = $this->db->prepare($sqlWithImage);
            $stmtWithImage->execute($params);
            $rooms = $stmtWithImage->fetchAll();
        } catch (\PDOException $e) {
            // Column doesn't exist yet, use rooms without image
            foreach ($rooms as &$room) {
                $room['room_type_image'] = null;
            }
        }

        return $rooms;
    }

    public function groupedAvailability(string $startDate, string $endDate): array
    {
        $rooms = $this->allAvailableBetween($startDate, $endDate);

        $grouped = [];

        foreach ($rooms as $room) {
            $grouped[$room['room_type_name']]['rooms'][] = $room;
        }

        return $grouped;
    }

    public function updateStatus(int $roomId, string $status): void
    {
        $sql = 'UPDATE rooms SET status = :status WHERE id = :id';
        $params = [
            'status' => $status,
            'id' => $roomId,
        ];
        $condition = $this->tenantCondition('rooms', $params);
        if ($condition) {
            $sql .= ' AND rooms.tenant_id = ';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function housekeepingBoard(): array
    {
        $params = [];
        // Check if image column exists, use COALESCE to handle missing column gracefully
        $sql = '
            SELECT rooms.*, room_types.name AS room_type_name
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE 1 = 1
        ';
        $sql .= $this->tenantCondition('rooms', $params);
        $sql .= '
            ORDER BY rooms.floor, rooms.room_number
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        // Try to add room_type_image if column exists
        try {
            $checkStmt = $this->db->query('SELECT image FROM room_types LIMIT 1');
            $checkStmt->fetch();
            // Column exists, fetch with image
            $sqlWithImage = '
                SELECT rooms.*, room_types.name AS room_type_name, room_types.image AS room_type_image
                FROM rooms
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE 1 = 1
            ';
            $sqlWithImage .= $this->tenantCondition('rooms', $params);
            $sqlWithImage .= ' ORDER BY rooms.floor, rooms.room_number';
            $stmtWithImage = $this->db->prepare($sqlWithImage);
            $stmtWithImage->execute($params);
            $rooms = $stmtWithImage->fetchAll();
        } catch (\PDOException $e) {
            // Column doesn't exist yet, use rooms without image
            foreach ($rooms as &$room) {
                $room['room_type_image'] = null;
            }
        }

        return $rooms;
    }

    public function find(int $id): ?array
    {
        $params = ['id' => $id];
        $sql = 'SELECT * FROM rooms WHERE id = :id';
        $sql .= $this->tenantCondition('rooms', $params);
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $room = $stmt->fetch();
        return $room ?: null;
    }

    public function isAvailable(int $roomId, string $startDate, string $endDate): bool
    {
        $params = [
            'room' => $roomId,
            'start' => $startDate,
            'end' => $endDate,
        ];
        $sql = '
            SELECT COUNT(*) FROM reservations
            WHERE room_id = :room
            AND NOT (check_out <= :start OR check_in >= :end)
        ';
        $sql .= $this->tenantCondition('reservations', $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() === 0;
    }

    public function listWithTypes(?int $limit = null, ?int $roomTypeId = null): array
    {
        $params = [];
        $sql = '
            SELECT rooms.*, room_types.name AS room_type_name, room_types.base_rate, room_types.description AS room_type_description, room_types.amenities AS room_type_amenities
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE 1 = 1
        ';
        $sql .= $this->tenantCondition('rooms', $params);
        if ($roomTypeId !== null) {
            $sql .= ' AND rooms.room_type_id = :room_type_id';
            $params['room_type_id'] = $roomTypeId;
        }
        $sql .= ' ORDER BY rooms.room_number';
        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        // Try to add room_type_image if column exists
        try {
            $checkStmt = $this->db->query('SELECT image FROM room_types LIMIT 1');
            $checkStmt->fetch();
            // Column exists, fetch with image
            $sqlWithImage = '
                SELECT rooms.*, room_types.name AS room_type_name, room_types.base_rate, room_types.description AS room_type_description, room_types.amenities AS room_type_amenities, room_types.image AS room_type_image
                FROM rooms
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE 1 = 1
            ';
            $sqlWithImage .= $this->tenantCondition('rooms', $params);
            if ($roomTypeId !== null) {
                $sqlWithImage .= ' AND rooms.room_type_id = :room_type_id';
            }
            $sqlWithImage .= ' ORDER BY rooms.room_number';
            if ($limit) {
                $sqlWithImage .= ' LIMIT ' . (int)$limit;
            }
            $stmtWithImage = $this->db->prepare($sqlWithImage);
            $stmtWithImage->execute($params);
            $rooms = $stmtWithImage->fetchAll();
        } catch (\PDOException $e) {
            // Column doesn't exist yet, use rooms without image
            foreach ($rooms as &$room) {
                $room['room_type_image'] = null;
            }
        }

        return $rooms;
    }

    public function update(int $id, array $data): void
    {
        $allowedFields = ['room_number', 'display_name', 'room_type_id', 'floor', 'status', 'image'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value ?: null;
            }
        }

        if (!$sets) {
            return;
        }

        $sql = 'UPDATE rooms SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $tenantCondition = $this->tenantCondition('rooms', $params);
        if ($tenantCondition) {
            $sql .= $tenantCondition;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function all(): array
    {
        $params = [];
        $sql = '
            SELECT rooms.*, room_types.name AS room_type_name
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE 1 = 1
        ';
        $sql .= $this->tenantCondition('rooms', $params);
        $sql .= ' ORDER BY rooms.room_number';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        // Try to add room_type_image if column exists
        try {
            $checkStmt = $this->db->query('SELECT image FROM room_types LIMIT 1');
            $checkStmt->fetch();
            // Column exists, fetch with image
            $sqlWithImage = '
                SELECT rooms.*, room_types.name AS room_type_name, room_types.image AS room_type_image
                FROM rooms
                INNER JOIN room_types ON room_types.id = rooms.room_type_id
                WHERE 1 = 1
            ';
            $sqlWithImage .= $this->tenantCondition('rooms', $params);
            $sqlWithImage .= ' ORDER BY rooms.room_number';
            $stmtWithImage = $this->db->prepare($sqlWithImage);
            $stmtWithImage->execute($params);
            $rooms = $stmtWithImage->fetchAll();
        } catch (\PDOException $e) {
            // Column doesn't exist yet, use rooms without image
            foreach ($rooms as &$room) {
                $room['room_type_image'] = null;
            }
        }

        return $rooms;
    }
}


