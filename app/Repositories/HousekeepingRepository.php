<?php

namespace App\Repositories;

use PDO;

class HousekeepingRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    protected function tenantCondition(string $alias, array &$params): string
    {
        return '';
    }

    public function getHousekeepingBoard(): array
    {
        $params = [];
        $sql = "
            SELECT 
                rooms.*,
                room_types.name AS room_type_name,
                (SELECT COUNT(*) FROM housekeeping_tasks WHERE housekeeping_tasks.room_id = rooms.id AND housekeeping_tasks.status IN ('pending','in_progress')) AS pending_tasks,
                (SELECT users.name FROM housekeeping_tasks 
                 INNER JOIN users ON users.id = housekeeping_tasks.assigned_to 
                 WHERE housekeeping_tasks.room_id = rooms.id 
                 AND housekeeping_tasks.status = 'in_progress' 
                 LIMIT 1) AS assigned_housekeeper,
                (SELECT MAX(completed_at) FROM housekeeping_tasks 
                 WHERE housekeeping_tasks.room_id = rooms.id 
                 AND housekeeping_tasks.status = 'completed') AS last_cleaned,
                (SELECT is_active FROM room_dnd_status 
                 WHERE room_dnd_status.room_id = rooms.id 
                 AND room_dnd_status.is_active = 1 
                 LIMIT 1) AS is_dnd
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE 1 = 1
        ";
        $sql .= $this->tenantCondition('rooms', $params);
        $sql .= " ORDER BY rooms.floor, rooms.room_number";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRoomsByStatus(string $status): array
    {
        $params = ['status' => $status];
        $sql = "
            SELECT rooms.*, room_types.name AS room_type_name
            FROM rooms
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            WHERE rooms.status = :status
        ";
        $sql .= $this->tenantCondition('rooms', $params);
        $sql .= " ORDER BY rooms.floor, rooms.room_number";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateRoomStatus(int $roomId, string $status, ?int $changedBy = null, ?string $reason = null, ?int $relatedReservationId = null, ?int $relatedTaskId = null): int
    {
        // Get current status
        $stmt = $this->db->prepare("SELECT status FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $roomId]);
        $currentRoom = $stmt->fetch();
        $previousStatus = $currentRoom['status'] ?? null;

        // Update room status
        $stmt = $this->db->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $status,
            'id' => $roomId,
        ]);

        // Log status change and return log ID
        return $this->logRoomStatusChange($roomId, $previousStatus, $status, $changedBy, $reason, $relatedReservationId, $relatedTaskId);
    }

    public function logRoomStatusChange(int $roomId, ?string $previousStatus, string $newStatus, ?int $changedBy = null, ?string $reason = null, ?int $relatedReservationId = null, ?int $relatedTaskId = null): int
    {
        $sql = "
            INSERT INTO room_status_logs 
            (room_id, previous_status, new_status, changed_by, reason, related_reservation_id, related_task_id)
            VALUES 
            (:room_id, :previous_status, :new_status, :changed_by, :reason, :related_reservation_id, :related_task_id)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $roomId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'related_reservation_id' => $relatedReservationId,
            'related_task_id' => $relatedTaskId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getRoomStatusHistory(int $roomId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                room_status_logs.*,
                users.name AS changed_by_name,
                reservations.reference AS reservation_reference
            FROM room_status_logs
            LEFT JOIN users ON users.id = room_status_logs.changed_by
            LEFT JOIN reservations ON reservations.id = room_status_logs.related_reservation_id
            WHERE room_status_logs.room_id = :room_id
            ORDER BY room_status_logs.created_at DESC
            LIMIT " . (int)$limit . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $roomId,
        ]);
        return $stmt->fetchAll();
    }

    public function getTasksForHousekeeper(?int $assignedTo, ?string $status = null, int $limit = 100): array
    {
        $params = [];
        $sql = "
            SELECT 
                housekeeping_tasks.*,
                rooms.room_number,
                rooms.display_name,
                rooms.floor,
                room_types.name AS room_type_name,
                assigned_users.name AS assigned_name,
                inspector_users.name AS inspector_name,
                creator_users.name AS creator_name
            FROM housekeeping_tasks
            INNER JOIN rooms ON rooms.id = housekeeping_tasks.room_id
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            LEFT JOIN users AS assigned_users ON assigned_users.id = housekeeping_tasks.assigned_to
            LEFT JOIN users AS inspector_users ON inspector_users.id = housekeeping_tasks.inspected_by
            LEFT JOIN users AS creator_users ON creator_users.id = housekeeping_tasks.created_by
            WHERE 1 = 1
        ";

        if ($assignedTo !== null) {
            $sql .= " AND housekeeping_tasks.assigned_to = :assigned_to";
            $params['assigned_to'] = $assignedTo;
        }

        if ($status !== null) {
            $sql .= " AND housekeeping_tasks.status = :status";
            $params['status'] = $status;
        }

        $sql .= $this->tenantCondition('housekeeping_tasks', $params);
        // LIMIT cannot use bound parameters in MySQL/MariaDB, so cast to int and include directly
        $limitValue = (int)$limit;
        $sql .= " ORDER BY housekeeping_tasks.priority DESC, housekeeping_tasks.scheduled_date ASC, housekeeping_tasks.created_at DESC LIMIT " . $limitValue;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createTask(array $data): int
    {
        $sql = "
            INSERT INTO housekeeping_tasks 
            (room_id, assigned_to, task_type, status, priority, scheduled_date, notes, created_by)
            VALUES 
            (:room_id, :assigned_to, :task_type, :status, :priority, :scheduled_date, :notes, :created_by)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $data['room_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'task_type' => $data['task_type'] ?? 'cleaning',
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateTask(int $taskId, array $data): void
    {
        $allowedFields = ['assigned_to', 'status', 'priority', 'scheduled_date', 'started_at', 'completed_at', 'inspected_at', 'inspected_by', 'notes', 'photos', 'inventory_used'];
        $updates = [];
        $params = ['id' => $taskId];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                if (in_array($field, ['photos', 'inventory_used']) && is_array($data[$field])) {
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            return;
        }

        $sql = "UPDATE housekeeping_tasks SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function findTaskById(int $taskId): ?array
    {
        $sql = "
            SELECT 
                housekeeping_tasks.*,
                rooms.room_number,
                rooms.display_name,
                rooms.floor,
                room_types.name AS room_type_name,
                assigned_users.name AS assigned_name,
                inspector_users.name AS inspector_name,
                creator_users.name AS creator_name
            FROM housekeeping_tasks
            INNER JOIN rooms ON rooms.id = housekeeping_tasks.room_id
            INNER JOIN room_types ON room_types.id = rooms.room_type_id
            LEFT JOIN users AS assigned_users ON assigned_users.id = housekeeping_tasks.assigned_to
            LEFT JOIN users AS inspector_users ON inspector_users.id = housekeeping_tasks.inspected_by
            LEFT JOIN users AS creator_users ON creator_users.id = housekeeping_tasks.created_by
            WHERE housekeeping_tasks.id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $taskId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getDailyStats(string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $params = ['date' => $date];

        $sql = "
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'inspected' THEN 1 ELSE 0 END) as inspected_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM housekeeping_tasks
            WHERE DATE(scheduled_date) = :date OR DATE(created_at) = :date
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    public function setDNDStatus(int $roomId, bool $isActive, ?int $reservationId = null, ?int $activatedBy = null, ?string $reason = null): void
    {
        if ($isActive) {
            // Deactivate any existing DND for this room
            $stmt = $this->db->prepare("UPDATE room_dnd_status SET is_active = 0, deactivated_at = NOW() WHERE room_id = :room_id AND is_active = 1");
            $stmt->execute(['room_id' => $roomId]);

            // Create new DND entry
            $sql = "
                INSERT INTO room_dnd_status 
                (room_id, reservation_id, is_active, activated_by, reason)
                VALUES 
                (:room_id, :reservation_id, 1, :activated_by, :reason)
                ON DUPLICATE KEY UPDATE is_active = 1, activated_at = NOW(), reason = :reason
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'room_id' => $roomId,
                'reservation_id' => $reservationId,
                'activated_by' => $activatedBy,
                'reason' => $reason,
            ]);

            // Update room status
            $this->updateRoomStatus($roomId, 'do_not_disturb', $activatedBy, $reason, $reservationId);
        } else {
            // Deactivate DND
            $stmt = $this->db->prepare("UPDATE room_dnd_status SET is_active = 0, deactivated_at = NOW() WHERE room_id = :room_id AND is_active = 1");
            $stmt->execute(['room_id' => $roomId]);

            // Revert room status based on reservation status
            $stmt = $this->db->prepare("SELECT r.status, res.id as reservation_id FROM rooms r LEFT JOIN reservations res ON res.room_id = r.id AND res.room_status = 'in_house' WHERE r.id = :room_id");
            $stmt->execute(['room_id' => $roomId]);
            $room = $stmt->fetch();

            if ($room && $room['reservation_id']) {
                $this->updateRoomStatus($roomId, 'occupied', $activatedBy, 'DND deactivated', $room['reservation_id']);
            } else {
                $this->updateRoomStatus($roomId, 'available', $activatedBy, 'DND deactivated');
            }
        }
    }

    public function isRoomDND(int $roomId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM room_dnd_status WHERE room_id = :room_id AND is_active = 1");
        $stmt->execute(['room_id' => $roomId]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }
}

