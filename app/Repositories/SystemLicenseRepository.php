<?php

namespace App\Repositories;

use PDO;

class SystemLicenseRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function getCurrent(): ?array
    {
        // Check if table exists first
        $tableExists = $this->db->query("SHOW TABLES LIKE 'system_license'")->fetch();
        if (!$tableExists) {
            return null;
        }

        $stmt = $this->db->query('SELECT * FROM system_license ORDER BY id DESC LIMIT 1');
        $license = $stmt->fetch();

        return $license ?: null;
    }

    public function createOrUpdate(array $data): int
    {
        $current = $this->getCurrent();

        if ($current) {
            $stmt = $this->db->prepare('
                UPDATE system_license SET
                    license_key = ?,
                    hardware_fingerprint = ?,
                    plan_type = ?,
                    status = ?,
                    expires_at = ?,
                    verification_url = ?,
                    updated_at = NOW()
                WHERE id = ?
            ');

            $stmt->execute([
                $data['license_key'],
                $data['hardware_fingerprint'] ?? null,
                $data['plan_type'] ?? 'monthly',
                $data['status'] ?? 'active',
                $data['expires_at'] ?? null,
                $data['verification_url'] ?? null,
                $current['id'],
            ]);

            return $current['id'];
        }

        $stmt = $this->db->prepare('
            INSERT INTO system_license (license_key, hardware_fingerprint, plan_type, status, expires_at, verification_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['license_key'],
            $data['hardware_fingerprint'] ?? null,
            $data['plan_type'] ?? 'monthly',
            $data['status'] ?? 'active',
            $data['expires_at'] ?? null,
            $data['verification_url'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE system_license SET status = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function updateLastVerified(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE system_license SET last_verified_at = NOW() WHERE id = ?');
        return $stmt->execute([$id]);
    }
}

