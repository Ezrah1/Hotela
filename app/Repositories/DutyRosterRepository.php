<?php

namespace App\Repositories;

use PDO;

class DutyRosterRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Create a new duty roster
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO duty_rosters (title, period_type, start_date, end_date, status, created_by)
            VALUES (:title, :period_type, :start_date, :end_date, :status, :created_by)
        ');
        $stmt->execute([
            'title' => $data['title'],
            'period_type' => $data['period_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'],
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Publish a roster and notify staff
     */
    public function publish(int $rosterId, int $publishedBy): bool
    {
        $stmt = $this->db->prepare('
            UPDATE duty_rosters 
            SET status = "published",
                published_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute(['id' => $rosterId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Add shift assignment to roster
     */
    public function addAssignment(int $rosterId, array $assignment): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO roster_assignments 
            (roster_id, user_id, shift_date, shift_start_time, shift_end_time, role_key, location, notes)
            VALUES 
            (:roster_id, :user_id, :shift_date, :shift_start_time, :shift_end_time, :role_key, :location, :notes)
            ON DUPLICATE KEY UPDATE
                shift_end_time = VALUES(shift_end_time),
                role_key = VALUES(role_key),
                location = VALUES(location),
                notes = VALUES(notes)
        ');
        $stmt->execute([
            'roster_id' => $rosterId,
            'user_id' => $assignment['user_id'],
            'shift_date' => $assignment['shift_date'],
            'shift_start_time' => $assignment['shift_start_time'],
            'shift_end_time' => $assignment['shift_end_time'],
            'role_key' => $assignment['role_key'],
            'location' => $assignment['location'] ?? null,
            'notes' => $assignment['notes'] ?? null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Check for overlapping shifts
     */
    public function hasOverlap(int $userId, string $shiftDate, string $shiftStart, string $shiftEnd, ?int $excludeAssignmentId = null): bool
    {
        $params = [
            'user_id' => $userId,
            'shift_date' => $shiftDate,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
        ];
        
        $excludeClause = $excludeAssignmentId ? 'AND id != :exclude_id' : '';
        if ($excludeAssignmentId) {
            $params['exclude_id'] = $excludeAssignmentId;
        }
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM roster_assignments 
            WHERE user_id = :user_id 
            AND shift_date = :shift_date
            AND (
                (shift_start_time < :shift_end AND shift_end_time > :shift_start)
            )
            {$excludeClause}
        ");
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get roster with assignments
     */
    public function findWithAssignments(int $rosterId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.*, u.name AS created_by_name
            FROM duty_rosters r
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.id = :id
        ');
        $stmt->execute(['id' => $rosterId]);
        $roster = $stmt->fetch();
        
        if (!$roster) {
            return null;
        }
        
        // Get assignments
        $assignmentsStmt = $this->db->prepare('
            SELECT ra.*, u.name AS user_name, u.email AS user_email, u.role_key AS user_role
            FROM roster_assignments ra
            INNER JOIN users u ON u.id = ra.user_id
            WHERE ra.roster_id = :roster_id
            ORDER BY ra.shift_date, ra.shift_start_time
        ');
        $assignmentsStmt->execute(['roster_id' => $rosterId]);
        $roster['assignments'] = $assignmentsStmt->fetchAll();
        
        return $roster;
    }

    /**
     * Get rosters for a date range
     */
    public function getForPeriod(?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];
        
        if ($startDate) {
            $conditions[] = 'end_date >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'start_date <= :end_date';
            $params['end_date'] = $endDate;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT r.*, u.name AS created_by_name
            FROM duty_rosters r
            LEFT JOIN users u ON u.id = r.created_by
            {$whereClause}
            ORDER BY r.start_date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Get user's assigned shifts for a date
     */
    public function getUserShifts(int $userId, string $date): array
    {
        $stmt = $this->db->prepare('
            SELECT ra.*, r.title AS roster_title, r.status AS roster_status
            FROM roster_assignments ra
            INNER JOIN duty_rosters r ON r.id = ra.roster_id
            WHERE ra.user_id = :user_id
            AND ra.shift_date = :date
            AND r.status = "published"
            ORDER BY ra.shift_start_time
        ');
        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Check if user has assigned shift for current time
     */
    public function hasAssignedShift(int $userId, string $date, string $time): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM roster_assignments ra
            INNER JOIN duty_rosters r ON r.id = ra.roster_id
            WHERE ra.user_id = :user_id
            AND ra.shift_date = :date
            AND r.status = "published"
            AND :time >= ra.shift_start_time
            AND :time <= ra.shift_end_time
        ');
        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
            'time' => $time,
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get department manpower count for a date
     */
    public function getDepartmentManpower(string $department, string $date, string $time): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT ra.user_id)
            FROM roster_assignments ra
            INNER JOIN duty_rosters r ON r.id = ra.roster_id
            INNER JOIN users u ON u.id = ra.user_id
            WHERE ra.shift_date = :date
            AND r.status = "published"
            AND :time >= ra.shift_start_time
            AND :time <= ra.shift_end_time
            AND u.role_key = :department
        ');
        $stmt->execute([
            'date' => $date,
            'time' => $time,
            'department' => $department,
        ]);
        
        return (int)$stmt->fetchColumn();
    }
}

