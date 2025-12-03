<?php

namespace App\Repositories;

use PDO;

class AttendanceRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Check if user is currently present (checked in and not checked out)
     */
    public function isPresent(int $userId): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM attendance_logs 
            WHERE user_id = :user_id 
            AND status = "present"
            AND DATE(check_in_time) = CURDATE()
            AND (override_expires_at IS NULL OR override_expires_at > NOW())
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if user has checked in today (alias for isPresent)
     */
    public function isCheckedIn(int $userId): bool
    {
        return $this->isPresent($userId);
    }

    /**
     * Check if user has been checked out today
     */
    public function isCheckedOut(int $userId): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM attendance_logs 
            WHERE user_id = :user_id 
            AND status = "checked_out"
            AND DATE(check_in_time) = CURDATE()
            AND check_out_time IS NOT NULL
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get current attendance record for user
     */
    public function getCurrentAttendance(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * 
            FROM attendance_logs 
            WHERE user_id = :user_id 
            AND status = "present"
            AND DATE(check_in_time) = CURDATE()
            ORDER BY check_in_time DESC
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Check in a staff member
     * Supports both old signature (userId, notes) and new signature (userId, checkedInBy, notes)
     */
    public function checkIn(int $userId, $checkedInByOrNotes = null, ?string $notes = null): int
    {
        // Handle old signature: checkIn(userId, notes)
        if (is_string($checkedInByOrNotes) || $checkedInByOrNotes === null) {
            $notes = $checkedInByOrNotes;
            $checkedInBy = $_SESSION['user_id'] ?? 0; // Use current logged-in user
        } else {
            // New signature: checkIn(userId, checkedInBy, notes)
            $checkedInBy = (int)$checkedInByOrNotes;
        }
        
        // First, check out any existing attendance for today
        $this->checkOutExisting($userId, $checkedInBy);
        
        $stmt = $this->db->prepare('
            INSERT INTO attendance_logs (user_id, check_in_time, checked_in_by, status, notes)
            VALUES (:user_id, NOW(), :checked_in_by, "present", :notes)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'checked_in_by' => $checkedInBy,
            'notes' => $notes,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Check out a staff member
     * Supports both old signature (userId, notes) and new signature (userId, checkedOutBy, notes)
     */
    public function checkOut(int $userId, $checkedOutByOrNotes = null, ?string $notes = null): bool
    {
        // Handle old signature: checkOut(userId, notes)
        if (is_string($checkedOutByOrNotes) || $checkedOutByOrNotes === null) {
            $notes = $checkedOutByOrNotes;
            $checkedOutBy = $_SESSION['user_id'] ?? 0; // Use current logged-in user
        } else {
            // New signature: checkOut(userId, checkedOutBy, notes)
            $checkedOutBy = (int)$checkedOutByOrNotes;
        }
        
        $current = $this->getCurrentAttendance($userId);
        if (!$current) {
            return false;
        }
        
        $stmt = $this->db->prepare('
            UPDATE attendance_logs 
            SET check_out_time = NOW(),
                checked_out_by = :checked_out_by,
                status = "checked_out",
                notes = COALESCE(:notes, notes)
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $current['id'],
            'checked_out_by' => $checkedOutBy,
            'notes' => $notes,
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Check out any existing attendance for today
     */
    protected function checkOutExisting(int $userId, int $checkedOutBy): void
    {
        $stmt = $this->db->prepare('
            UPDATE attendance_logs 
            SET check_out_time = NOW(),
                checked_out_by = :checked_out_by,
                status = "checked_out"
            WHERE user_id = :user_id 
            AND status = "present"
            AND DATE(check_in_time) = CURDATE()
        ');
        $stmt->execute([
            'user_id' => $userId,
            'checked_out_by' => $checkedOutBy,
        ]);
    }

    /**
     * Get attendance history for a user
     */
    public function getHistory(int $userId, ?string $startDate = null, ?string $endDate = null, int $limit = 50): array
    {
        $params = ['user_id' => $userId];
        $conditions = ['user_id = :user_id'];
        
        if ($startDate) {
            $conditions[] = 'DATE(check_in_time) >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'DATE(check_in_time) <= :end_date';
            $params['end_date'] = $endDate;
        }
        
        $sql = '
            SELECT al.*, 
                   u1.name AS checked_in_by_name,
                   u2.name AS checked_out_by_name,
                   u3.name AS override_granted_by_name
            FROM attendance_logs al
            LEFT JOIN users u1 ON u1.id = al.checked_in_by
            LEFT JOIN users u2 ON u2.id = al.checked_out_by
            LEFT JOIN users u3 ON u3.id = al.override_granted_by
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY al.check_in_time DESC
            LIMIT ' . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Get all present staff
     */
    public function getPresentStaff(): array
    {
        $stmt = $this->db->prepare('
            SELECT al.*, u.name, u.email, u.role_key
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            WHERE al.status = "present"
            AND DATE(al.check_in_time) = CURDATE()
            AND (al.override_expires_at IS NULL OR al.override_expires_at > NOW())
            ORDER BY al.check_in_time DESC
        ');
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get attendance statistics for a date range
     */
    public function getStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];
        
        if ($startDate) {
            $conditions[] = 'DATE(check_in_time) >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'DATE(check_in_time) <= :end_date';
            $params['end_date'] = $endDate;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT 
                COUNT(DISTINCT user_id) AS total_staff,
                COUNT(*) AS total_check_ins,
                SUM(CASE WHEN status = 'present' AND DATE(check_in_time) = CURDATE() THEN 1 ELSE 0 END) AS currently_present,
                AVG(TIMESTAMPDIFF(HOUR, check_in_time, COALESCE(check_out_time, NOW()))) AS avg_hours
            FROM attendance_logs
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() ?: [];
    }

    /**
     * Get all today's attendance records
     */
    public function getAllTodayAttendance(): array
    {
        $stmt = $this->db->prepare('
            SELECT al.*, 
                   u.name AS user_name,
                   u.email AS user_email,
                   u.role_key AS user_role,
                   u1.name AS checked_in_by_name,
                   u2.name AS checked_out_by_name
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            LEFT JOIN users u1 ON u1.id = al.checked_in_by
            LEFT JOIN users u2 ON u2.id = al.checked_out_by
            WHERE DATE(al.check_in_time) = CURDATE()
            ORDER BY al.check_in_time DESC
        ');
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get today's attendance for a specific user
     */
    public function getTodayAttendance(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT al.*, 
                   u1.name AS checked_in_by_name,
                   u2.name AS checked_out_by_name
            FROM attendance_logs al
            LEFT JOIN users u1 ON u1.id = al.checked_in_by
            LEFT JOIN users u2 ON u2.id = al.checked_out_by
            WHERE al.user_id = :user_id
            AND DATE(al.check_in_time) = CURDATE()
            ORDER BY al.check_in_time DESC
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Get attendance history for a user (alias for getHistory with days parameter)
     */
    public function getAttendanceHistory(int $userId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        return $this->getHistory($userId, $startDate, null, 100);
    }

    /**
     * Get all attendance records with filters
     */
    public function getAllAttendanceRecords(?string $startDate = null, ?string $endDate = null, ?int $userId = null, int $limit = 200): array
    {
        $params = [];
        $conditions = [];
        
        if ($startDate) {
            $conditions[] = 'DATE(al.check_in_time) >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'DATE(al.check_in_time) <= :end_date';
            $params['end_date'] = $endDate;
        }
        if ($userId) {
            $conditions[] = 'al.user_id = :user_id';
            $params['user_id'] = $userId;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT al.*, 
                   u.name AS user_name,
                   u.email AS user_email,
                   u.role_key AS user_role,
                   u1.name AS checked_in_by_name,
                   u2.name AS checked_out_by_name
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            LEFT JOIN users u1 ON u1.id = al.checked_in_by
            LEFT JOIN users u2 ON u2.id = al.checked_out_by
            {$whereClause}
            ORDER BY al.check_in_time DESC
            LIMIT " . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Get attendance statistics for a specific user
     */
    public function getAttendanceStatistics(?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];
        
        if ($userId) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }
        if ($startDate) {
            $conditions[] = 'DATE(check_in_time) >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'DATE(check_in_time) <= :end_date';
            $params['end_date'] = $endDate;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT 
                COUNT(DISTINCT DATE(check_in_time)) AS days_worked,
                COUNT(*) AS total_check_ins,
                AVG(TIMESTAMPDIFF(HOUR, check_in_time, COALESCE(check_out_time, NOW()))) AS avg_hours_per_day,
                MIN(check_in_time) AS first_check_in,
                MAX(COALESCE(check_out_time, check_in_time)) AS last_activity
            FROM attendance_logs
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() ?: [];
    }

    /**
     * Get per-employee attendance statistics
     */
    public function getPerEmployeeStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];
        
        if ($startDate) {
            $conditions[] = 'DATE(al.check_in_time) >= :start_date';
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $conditions[] = 'DATE(al.check_in_time) <= :end_date';
            $params['end_date'] = $endDate;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT 
                u.id AS user_id,
                u.name AS user_name,
                u.email,
                r.key AS role_key,
                COUNT(DISTINCT DATE(al.check_in_time)) AS total_days,
                SUM(TIMESTAMPDIFF(HOUR, al.check_in_time, COALESCE(al.check_out_time, NOW()))) AS total_hours,
                AVG(TIMESTAMPDIFF(HOUR, al.check_in_time, COALESCE(al.check_out_time, NOW()))) AS avg_hours_per_day,
                MIN(TIMESTAMPDIFF(HOUR, al.check_in_time, COALESCE(al.check_out_time, NOW()))) AS min_hours,
                MAX(TIMESTAMPDIFF(HOUR, al.check_in_time, COALESCE(al.check_out_time, NOW()))) AS max_hours
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            LEFT JOIN roles r ON r.key = u.role_key
            {$whereClause}
            GROUP BY u.id, u.name, u.email, r.key
            ORDER BY total_days DESC, total_hours DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Detect attendance anomalies
     */
    public function detectAnomalies(?int $userId = null, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $params = ['start_date' => $startDate];
        $conditions = ['DATE(check_in_time) >= :start_date'];
        
        if ($userId) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }
        
        // Find check-ins outside normal hours (before 6 AM or after 10 PM)
        // Exclude ignored anomalies
        $sql = "
            SELECT al.*, u.name AS user_name, u.role_key AS user_role
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            LEFT JOIN ignored_anomalies ia ON ia.attendance_log_id = al.id
            WHERE " . implode(' AND ', $conditions) . "
            AND (HOUR(check_in_time) < 6 OR HOUR(check_in_time) >= 22)
            AND ia.id IS NULL
            ORDER BY al.check_in_time DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Ignore an anomaly
     */
    public function ignoreAnomaly(int $attendanceLogId, int $ignoredBy, ?string $reason = null): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO ignored_anomalies (attendance_log_id, ignored_by, reason)
            VALUES (:attendance_log_id, :ignored_by, :reason)
            ON DUPLICATE KEY UPDATE 
                ignored_by = VALUES(ignored_by),
                reason = VALUES(reason),
                created_at = CURRENT_TIMESTAMP
        ');
        
        return $stmt->execute([
            'attendance_log_id' => $attendanceLogId,
            'ignored_by' => $ignoredBy,
            'reason' => $reason
        ]);
    }

    /**
     * Unignore an anomaly (remove from ignored list)
     */
    public function unignoreAnomaly(int $attendanceLogId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ignored_anomalies WHERE attendance_log_id = :attendance_log_id');
        return $stmt->execute(['attendance_log_id' => $attendanceLogId]);
    }

    /**
     * Check if an anomaly is ignored
     */
    public function isAnomalyIgnored(int $attendanceLogId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ignored_anomalies WHERE attendance_log_id = :attendance_log_id');
        $stmt->execute(['attendance_log_id' => $attendanceLogId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get best attendance records
     */
    public function getBestAttendance(int $limit = 10): array
    {
        $sql = "
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(DISTINCT DATE(al.check_in_time)) AS days_worked,
                AVG(TIMESTAMPDIFF(HOUR, al.check_in_time, COALESCE(al.check_out_time, NOW()))) AS avg_hours
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            WHERE DATE(al.check_in_time) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY u.id, u.name, u.email
            ORDER BY days_worked DESC, avg_hours DESC
            LIMIT " . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
