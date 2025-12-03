<?php

namespace App\Repositories;

use PDO;

class LoginOverrideRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Create a temporary login override (alias for grantOverride)
     */
    public function create(int $userId, int $grantedBy, ?string $reason = null, int $durationHours = 1): int
    {
        return $this->grantOverride($userId, $grantedBy, $reason, $durationHours);
    }

    /**
     * Grant a temporary login override
     */
    public function grantOverride(int $userId, int $grantedBy, ?string $reason = null, int $durationHours = 1): int
    {
        // First, revoke any existing override
        $this->revokeOverride($userId);
        
        // Enforce 1 hour maximum as per requirements
        if ($durationHours > 1) {
            $durationHours = 1;
        }
        
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hour"));
        
        // Update or create attendance log with override
        $current = $this->getCurrentAttendance($userId);
        if ($current) {
            $stmt = $this->db->prepare('
                UPDATE attendance_logs 
                SET override_granted_by = :granted_by,
                    override_expires_at = :expires_at,
                    notes = COALESCE(CONCAT(notes, "\nOverride: ", :reason), :reason)
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $current['id'],
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt,
                'reason' => $reason,
            ]);
            return $current['id'];
        } else {
            // Create new attendance log with override
            $stmt = $this->db->prepare('
                INSERT INTO attendance_logs (user_id, check_in_time, override_granted_by, override_expires_at, status, notes)
                VALUES (:user_id, NOW(), :granted_by, :expires_at, "present", :reason)
            ');
            $stmt->execute([
                'user_id' => $userId,
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt,
                'reason' => $reason,
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Revoke active override for user
     */
    public function revokeOverride(int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE attendance_logs 
            SET override_expires_at = NULL,
                override_granted_by = NULL
            WHERE user_id = :user_id 
            AND override_expires_at > NOW()
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Get active override for user
     */
    public function getActiveOverride(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * 
            FROM attendance_logs 
            WHERE user_id = :user_id 
            AND override_expires_at IS NOT NULL
            AND override_expires_at > NOW()
            ORDER BY override_expires_at DESC
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Mark override as used (when user logs in with override)
     */
    public function markAsUsed(int $attendanceLogId): void
    {
        // Override is automatically tracked via expiration time
        // This method can be used for logging if needed
    }

    /**
     * Get current attendance (helper method)
     */
    protected function getCurrentAttendance(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * 
            FROM attendance_logs 
            WHERE user_id = :user_id 
            AND DATE(check_in_time) = CURDATE()
            ORDER BY check_in_time DESC
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetch() ?: null;
    }
}
