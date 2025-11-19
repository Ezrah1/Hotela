<?php

namespace App\Repositories;

class AttendanceRepository
{
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function checkIn(int $userId, ?string $notes = null): int
    {
        $today = date('Y-m-d');
        
        // Check if already checked in today
        $existing = $this->getTodayAttendance($userId);
        if ($existing && $existing['checked_in']) {
            throw new \RuntimeException('Already checked in today');
        }

        // If exists but checked out, create new record
        $checkInTime = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO staff_attendance (user_id, check_in_time, checked_in, checked_out, date, notes)
            VALUES (:user_id, :check_in_time, TRUE, FALSE, :date, :notes)
        ');

        $stmt->execute([
            'user_id' => $userId,
            'check_in_time' => $checkInTime,
            'date' => $today,
            'notes' => $notes,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function checkOut(int $userId, ?string $notes = null): void
    {
        $today = date('Y-m-d');
        $checkOutTime = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            UPDATE staff_attendance
            SET check_out_time = :check_out_time,
                checked_in = FALSE,
                checked_out = TRUE,
                notes = CASE WHEN notes IS NULL OR notes = "" THEN :notes ELSE CONCAT(notes, "\n", :notes) END,
                updated_at = NOW()
            WHERE user_id = :user_id
            AND date = :date
            AND checked_in = TRUE
            AND checked_out = FALSE
        ');

        $stmt->execute([
            'user_id' => $userId,
            'check_out_time' => $checkOutTime,
            'date' => $today,
            'notes' => $notes,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('No active check-in found for today');
        }
    }

    public function getTodayAttendance(int $userId): ?array
    {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare('
            SELECT * FROM staff_attendance
            WHERE user_id = :user_id
            AND date = :date
            ORDER BY check_in_time DESC
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId,
            'date' => $today,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function isCheckedIn(int $userId): bool
    {
        $attendance = $this->getTodayAttendance($userId);
        return $attendance && $attendance['checked_in'] && !$attendance['checked_out'];
    }

    public function isCheckedOut(int $userId): bool
    {
        $attendance = $this->getTodayAttendance($userId);
        return $attendance && $attendance['checked_out'];
    }

    public function getAttendanceHistory(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM staff_attendance
            WHERE user_id = :user_id
            ORDER BY date DESC, check_in_time DESC
            LIMIT :limit
        ');

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAllTodayAttendance(): array
    {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare('
            SELECT sa.*, u.name as user_name, u.email as user_email, u.role_key
            FROM staff_attendance sa
            INNER JOIN users u ON u.id = sa.user_id
            WHERE sa.date = :date
            ORDER BY sa.check_in_time DESC
        ');

        $stmt->execute(['date' => $today]);

        return $stmt->fetchAll();
    }

    public function getAllAttendanceRecords(?string $startDate = null, ?string $endDate = null, ?int $userId = null, int $limit = 100): array
    {
        $params = [];
        $conditions = [];

        if ($startDate) {
            $conditions[] = 'sa.date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = 'sa.date <= :end_date';
            $params['end_date'] = $endDate;
        }

        if ($userId) {
            $conditions[] = 'sa.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->db->prepare("
            SELECT sa.*, u.name as user_name, u.email as user_email, u.role_key
            FROM staff_attendance sa
            INNER JOIN users u ON u.id = sa.user_id
            {$whereClause}
            ORDER BY sa.date DESC, sa.check_in_time DESC
            LIMIT :limit
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $records = $stmt->fetchAll();
        
        // Calculate hours for each record
        foreach ($records as &$record) {
            if ($record['check_out_time']) {
                $checkIn = strtotime($record['check_in_time']);
                $checkOut = strtotime($record['check_out_time']);
                $record['hours_worked'] = round(($checkOut - $checkIn) / 3600, 2);
            } else {
                $record['hours_worked'] = null;
            }
        }

        return $records;
    }

    public function getDailyHours(int $userId, string $date): float
    {
        $stmt = $this->db->prepare('
            SELECT check_in_time, check_out_time
            FROM staff_attendance
            WHERE user_id = :user_id
            AND date = :date
            AND checked_out = TRUE
        ');

        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);

        $records = $stmt->fetchAll();
        $totalHours = 0;

        foreach ($records as $record) {
            if ($record['check_out_time']) {
                $checkIn = strtotime($record['check_in_time']);
                $checkOut = strtotime($record['check_out_time']);
                $totalHours += ($checkOut - $checkIn) / 3600;
            }
        }

        return round($totalHours, 2);
    }

    public function getWeeklyHours(int $userId, string $weekStart): float
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $stmt = $this->db->prepare('
            SELECT check_in_time, check_out_time
            FROM staff_attendance
            WHERE user_id = :user_id
            AND date >= :week_start
            AND date <= :week_end
            AND checked_out = TRUE
        ');

        $stmt->execute([
            'user_id' => $userId,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ]);

        $records = $stmt->fetchAll();
        $totalHours = 0;

        foreach ($records as $record) {
            if ($record['check_out_time']) {
                $checkIn = strtotime($record['check_in_time']);
                $checkOut = strtotime($record['check_out_time']);
                $totalHours += ($checkOut - $checkIn) / 3600;
            }
        }

        return round($totalHours, 2);
    }

    public function getMonthlyStats(int $userId, string $month): array
    {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));

        $stmt = $this->db->prepare('
            SELECT 
                COUNT(DISTINCT date) as days_worked,
                SUM(CASE WHEN checked_out = TRUE AND check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, check_in_time, check_out_time) / 3600.0 
                    ELSE 0 END) as total_hours
            FROM staff_attendance
            WHERE user_id = :user_id
            AND date >= :start_date
            AND date <= :end_date
        ');

        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $result = $stmt->fetch();
        return [
            'days_worked' => (int)($result['days_worked'] ?? 0),
            'total_hours' => round((float)($result['total_hours'] ?? 0), 2),
        ];
    }

    public function getAttendanceStatistics(?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $params = [];
        $conditions = [];

        if ($userId) {
            $conditions[] = 'sa.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($startDate) {
            $conditions[] = 'sa.date >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $conditions[] = 'sa.date <= :end_date';
            $params['end_date'] = $endDate;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->db->prepare("
            SELECT 
                sa.user_id,
                u.name as user_name,
                u.role_key,
                COUNT(DISTINCT sa.date) as total_days,
                SUM(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE 0 END) as total_hours,
                AVG(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE NULL END) as avg_hours_per_day,
                MIN(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE NULL END) as min_hours,
                MAX(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE NULL END) as max_hours
            FROM staff_attendance sa
            INNER JOIN users u ON u.id = sa.user_id
            {$whereClause}
            GROUP BY sa.user_id, u.name, u.role_key
            ORDER BY total_hours DESC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function detectAnomalies(?int $userId = null, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $params = ['start_date' => $startDate];
        $conditions = ['sa.date >= :start_date', 'sa.checked_out = TRUE', 'sa.check_out_time IS NOT NULL'];

        if ($userId) {
            $conditions[] = 'sa.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $stmt = $this->db->prepare("
            SELECT 
                sa.*,
                u.name as user_name,
                u.email as user_email,
                u.role_key,
                TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 as hours_worked,
                TIME(sa.check_in_time) as check_in_time_only,
                TIME(sa.check_out_time) as check_out_time_only
            FROM staff_attendance sa
            INNER JOIN users u ON u.id = sa.user_id
            {$whereClause}
            ORDER BY sa.date DESC
        ");

        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $anomalies = [];

        foreach ($records as $record) {
            $hours = (float)$record['hours_worked'];
            $issues = [];
            $sameTimeCount = 0;

            // Very short shifts (less than 1 hour)
            if ($hours < 1) {
                $issues[] = 'Very short shift (' . round($hours, 2) . ' hours)';
            }

            // Suspiciously long shifts (more than 16 hours)
            if ($hours > 16) {
                $issues[] = 'Unusually long shift (' . round($hours, 2) . ' hours)';
            }

            // Check for exact same check-in/check-out times across multiple days
            $checkInTime = $record['check_in_time_only'];
            $checkOutTime = $record['check_out_time_only'];

            if ($checkInTime && $checkOutTime) {
                $sameTimeCount = $this->countSameTimePattern($record['user_id'], $checkInTime, $checkOutTime, $startDate);
                if ($sameTimeCount >= 3) {
                    $issues[] = "Repeated exact times ({$sameTimeCount} times): {$checkInTime} - {$checkOutTime}";
                }
            }

            if (!empty($issues)) {
                $severity = 'medium';
                if ($hours < 1 || $hours > 16) {
                    $severity = 'high';
                } elseif ($sameTimeCount >= 5) {
                    $severity = 'high';
                }

                $anomalies[] = [
                    'record' => $record,
                    'issues' => $issues,
                    'severity' => $severity,
                ];
            }
        }

        return $anomalies;
    }

    protected function countSameTimePattern(int $userId, string $checkInTime, string $checkOutTime, string $startDate): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM staff_attendance
            WHERE user_id = :user_id
            AND date >= :start_date
            AND checked_out = TRUE
            AND TIME(check_in_time) = :check_in_time
            AND TIME(check_out_time) = :check_out_time
        ');

        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);

        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    public function getBestAttendance(int $limit = 10): array
    {
        $last30Days = date('Y-m-d', strtotime('-30 days'));

        $stmt = $this->db->prepare('
            SELECT 
                sa.user_id,
                u.name as user_name,
                u.email as user_email,
                u.role_key,
                COUNT(DISTINCT sa.date) as days_present,
                SUM(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE 0 END) as total_hours,
                AVG(CASE WHEN sa.checked_out = TRUE AND sa.check_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, sa.check_in_time, sa.check_out_time) / 3600.0 
                    ELSE NULL END) as avg_hours_per_day
            FROM staff_attendance sa
            INNER JOIN users u ON u.id = sa.user_id
            WHERE sa.date >= :start_date
            GROUP BY sa.user_id, u.name, u.email, u.role_key
            HAVING days_present > 0
            ORDER BY days_present DESC, total_hours DESC
            LIMIT :limit
        ');

        $stmt->bindValue(':start_date', $last30Days, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

