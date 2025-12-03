<?php

namespace App\Repositories;

use PDO;

class GuestRequestRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO guest_requests 
            (reservation_id, room_id, request_type, status, priority, guest_name, guest_phone, guest_email, request_details)
            VALUES 
            (:reservation_id, :room_id, :request_type, :status, :priority, :guest_name, :guest_phone, :guest_email, :request_details)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'reservation_id' => $data['reservation_id'],
            'room_id' => $data['room_id'],
            'request_type' => $data['request_type'] ?? 'cleaning',
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'request_details' => $data['request_details'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByReservation(int $reservationId): array
    {
        $sql = "
            SELECT 
                guest_requests.*,
                rooms.room_number,
                rooms.display_name,
                assigned_staff.name AS assigned_name
            FROM guest_requests
            INNER JOIN rooms ON rooms.id = guest_requests.room_id
            LEFT JOIN staff AS assigned_staff ON assigned_staff.id = guest_requests.assigned_to
            WHERE guest_requests.reservation_id = :reservation_id
            ORDER BY guest_requests.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['reservation_id' => $reservationId]);
        return $stmt->fetchAll();
    }

    public function findByRoom(int $roomId): array
    {
        $sql = "
            SELECT 
                guest_requests.*,
                reservations.reference AS reservation_reference,
                assigned_staff.name AS assigned_name
            FROM guest_requests
            INNER JOIN reservations ON reservations.id = guest_requests.reservation_id
            LEFT JOIN staff AS assigned_staff ON assigned_staff.id = guest_requests.assigned_to
            WHERE guest_requests.room_id = :room_id
            ORDER BY guest_requests.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $requestId, string $status, ?int $assignedTo = null): void
    {
        $updates = ['status' => $status];
        $params = ['id' => $requestId];

        if ($status === 'assigned' && $assignedTo) {
            $updates['assigned_to'] = $assignedTo;
            $updates['assigned_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'in_progress') {
            $updates['started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $updates['completed_at'] = date('Y-m-d H:i:s');
        }

        $updateFields = [];
        foreach ($updates as $field => $value) {
            $updateFields[] = "$field = :$field";
            $params[$field] = $value;
        }

        $sql = "UPDATE guest_requests SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function findById(int $requestId): ?array
    {
        $sql = "
            SELECT 
                guest_requests.*,
                rooms.room_number,
                rooms.display_name,
                reservations.reference AS reservation_reference,
                assigned_staff.name AS assigned_name
            FROM guest_requests
            INNER JOIN rooms ON rooms.id = guest_requests.room_id
            INNER JOIN reservations ON reservations.id = guest_requests.reservation_id
            LEFT JOIN staff AS assigned_staff ON assigned_staff.id = guest_requests.assigned_to
            WHERE guest_requests.id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $requestId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getPendingRequests(): array
    {
        $sql = "
            SELECT 
                guest_requests.*,
                rooms.room_number,
                rooms.display_name,
                reservations.reference AS reservation_reference
            FROM guest_requests
            INNER JOIN rooms ON rooms.id = guest_requests.room_id
            INNER JOIN reservations ON reservations.id = guest_requests.reservation_id
            WHERE guest_requests.status = 'pending'
            ORDER BY 
                CASE guest_requests.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                END ASC,
                guest_requests.created_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

