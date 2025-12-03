<?php

namespace App\Repositories;

use PDO;

class SupplierLoginCodeRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Create a new login code for a supplier
     */
    public function create(int $supplierId, string $email, string $code, int $expiresInMinutes = 15): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));
        
        $stmt = $this->db->prepare('
            INSERT INTO supplier_login_codes (supplier_id, email, code, expires_at)
            VALUES (:supplier_id, :email, :code, :expires_at)
        ');
        
        $stmt->execute([
            'supplier_id' => $supplierId,
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
            SELECT slc.*, s.id as supplier_id, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone
            FROM supplier_login_codes slc
            INNER JOIN suppliers s ON s.id = slc.supplier_id
            WHERE slc.email = :email
            AND slc.code = :code
            AND slc.expires_at > NOW()
            AND slc.used_at IS NULL
            AND s.portal_enabled = 1
            ORDER BY slc.created_at DESC
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
            UPDATE supplier_login_codes
            SET used_at = NOW()
            WHERE id = :id
        ');
        
        $stmt->execute(['id' => $id]);
    }

    /**
     * Check if email has a recent code request (rate limiting)
     */
    public function hasRecentRequest(string $email, int $minutes = 2): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count FROM supplier_login_codes
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

