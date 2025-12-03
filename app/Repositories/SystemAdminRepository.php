<?php

namespace App\Repositories;

use PDO;

class SystemAdminRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM system_admins WHERE username = :username AND is_active = 1');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM system_admins WHERE email = :email AND is_active = 1');
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM system_admins WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO system_admins (username, email, password_hash, encrypted_credentials, is_active)
            VALUES (:username, :email, :password_hash, :encrypted_credentials, :is_active)
        ');
        
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'encrypted_credentials' => $data['encrypted_credentials'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateLastActivity(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE system_admins SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateUsername(int $id, string $username): bool
    {
        $stmt = $this->db->prepare('UPDATE system_admins SET username = :username WHERE id = :id');
        return $stmt->execute(['id' => $id, 'username' => $username]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare('UPDATE system_admins SET password_hash = :password_hash WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    public function update2FASecret(int $id, ?string $secret): bool
    {
        $stmt = $this->db->prepare('UPDATE system_admins SET two_factor_secret = :secret WHERE id = :id');
        return $stmt->execute(['id' => $id, 'secret' => $secret]);
    }

    public function enable2FA(int $id, string $secret, array $backupCodes = []): bool
    {
        $stmt = $this->db->prepare('
            UPDATE system_admins 
            SET two_factor_enabled = 1, 
                two_factor_secret = :secret,
                two_factor_backup_codes = :backup_codes
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'secret' => $secret,
            'backup_codes' => !empty($backupCodes) ? json_encode($backupCodes) : null
        ]);
    }

    public function disable2FA(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE system_admins 
            SET two_factor_enabled = 0, 
                two_factor_secret = NULL,
                two_factor_backup_codes = NULL
            WHERE id = :id
        ');
        return $stmt->execute(['id' => $id]);
    }

    public function logAction(int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, ?array $details = null): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO system_audit_logs (system_admin_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (:admin_id, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent)
        ');
        
        $stmt->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}

