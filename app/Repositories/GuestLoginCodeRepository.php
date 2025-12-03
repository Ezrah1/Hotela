<?php

namespace App\Repositories;

use PDO;

class GuestLoginCodeRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Create a new login code for an email
     */
    public function create(string $email, string $code, int $expiresInMinutes = 15): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));
        
        $stmt = $this->db->prepare('
            INSERT INTO guest_login_codes (email, code, expires_at)
            VALUES (:email, :code, :expires_at)
        ');
        
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Find a valid code for an email
     */
    public function findValidCode(string $email, string $code): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM guest_login_codes
            WHERE email = :email
            AND code = :code
            AND expires_at > NOW()
            AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ');
        
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'code' => $code,
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Mark a code as used
     */
    public function markAsUsed(int $id): void
    {
        $stmt = $this->db->prepare('
            UPDATE guest_login_codes
            SET used_at = NOW()
            WHERE id = :id
        ');
        
        $stmt->execute(['id' => $id]);
    }

    /**
     * Clean up expired codes (optional cleanup method)
     */
    public function cleanupExpired(): int
    {
        $stmt = $this->db->prepare('
            DELETE FROM guest_login_codes
            WHERE expires_at < NOW()
            AND (used_at IS NOT NULL OR created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
        ');
        
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Check if email has a recent code request (rate limiting)
     */
    public function hasRecentRequest(string $email, int $minutes = 2): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count FROM guest_login_codes
            WHERE email = :email
            AND created_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
        ');
        
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'minutes' => $minutes,
        ]);
        
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }
}

