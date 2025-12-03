<?php

namespace App\Repositories;

use PDO;

class GuestAccountRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * Find account by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM guest_accounts 
            WHERE guest_email = :email
            LIMIT 1
        ');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Create a new guest account
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO guest_accounts (guest_email, password_hash, guest_name, guest_phone)
            VALUES (:guest_email, :password_hash, :guest_name, :guest_phone)
        ');
        $stmt->execute([
            'guest_email' => strtolower(trim($data['guest_email'])),
            'password_hash' => $data['password_hash'],
            'guest_name' => $data['guest_name'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update account password
     */
    public function updatePassword(string $email, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('
            UPDATE guest_accounts 
            SET password_hash = :password_hash, updated_at = NOW()
            WHERE guest_email = :guest_email
        ');
        return $stmt->execute([
            'password_hash' => $passwordHash,
            'guest_email' => strtolower(trim($email)),
        ]);
    }

    /**
     * Update last login time
     */
    public function updateLastLogin(string $email): bool
    {
        $stmt = $this->db->prepare('
            UPDATE guest_accounts 
            SET last_login_at = NOW()
            WHERE guest_email = :guest_email
        ');
        return $stmt->execute(['guest_email' => strtolower(trim($email))]);
    }

    /**
     * Update account info (name, phone)
     */
    public function updateInfo(string $email, array $data): bool
    {
        $updates = [];
        $params = ['guest_email' => strtolower(trim($email))];

        if (isset($data['guest_name'])) {
            $updates[] = 'guest_name = :guest_name';
            $params['guest_name'] = $data['guest_name'];
        }

        if (isset($data['guest_phone'])) {
            $updates[] = 'guest_phone = :guest_phone';
            $params['guest_phone'] = $data['guest_phone'];
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = 'updated_at = NOW()';
        $sql = 'UPDATE guest_accounts SET ' . implode(', ', $updates) . ' WHERE guest_email = :guest_email';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $email, string $password): bool
    {
        $account = $this->findByEmail($email);
        if (!$account) {
            return false;
        }

        return password_verify($password, $account['password_hash']);
    }

    /**
     * Check if account exists
     */
    public function exists(string $email): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count FROM guest_accounts 
            WHERE guest_email = :email
        ');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] ?? 0) > 0;
    }
}

