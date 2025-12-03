<?php

namespace App\Repositories;

use PDO;

class TenantRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM tenants ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $slug = $this->generateSlug($data['name']);
        $domain = $data['domain'] ?? $slug . '.hotela.local';
        
        $stmt = $this->db->prepare('
            INSERT INTO tenants (name, slug, domain, contact_email, contact_phone, status, settings)
            VALUES (:name, :slug, :domain, :contact_email, :contact_phone, :status, :settings)
        ');
        
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $slug,
            'domain' => $domain,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => $data['status'] ?? 'active',
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tenants WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getStats(int $tenantId): array
    {
        // Get user count
        $userStmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE tenant_id = :tenant_id');
        $userStmt->execute(['tenant_id' => $tenantId]);
        $userCount = (int)$userStmt->fetchColumn();

        // Get booking count (last 30 days)
        $bookingStmt = $this->db->prepare('
            SELECT COUNT(*) 
            FROM reservations 
            WHERE tenant_id = :tenant_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');
        $bookingStmt->execute(['tenant_id' => $tenantId]);
        $bookingCount = (int)$bookingStmt->fetchColumn();

        // Get license status
        // First get the director user ID
        $directorStmt = $this->db->prepare('
            SELECT id FROM users 
            WHERE tenant_id = :tenant_id AND role_key = "director" 
            LIMIT 1
        ');
        $directorStmt->execute(['tenant_id' => $tenantId]);
        $directorId = $directorStmt->fetchColumn();
        
        $license = null;
        if ($directorId) {
            $licenseStmt = $this->db->prepare('
                SELECT status, expires_at 
                FROM license_activations 
                WHERE director_user_id = :director_id
                ORDER BY activated_at DESC 
                LIMIT 1
            ');
            $licenseStmt->execute(['director_id' => $directorId]);
            $license = $licenseStmt->fetch(PDO::FETCH_ASSOC);
        }

        return [
            'user_count' => $userCount,
            'booking_count_30d' => $bookingCount,
            'license_status' => $license['status'] ?? 'inactive',
            'license_expires' => $license['expires_at'] ?? null,
        ];
    }
}

