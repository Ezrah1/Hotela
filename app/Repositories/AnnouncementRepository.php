<?php

namespace App\Repositories;

use PDO;

class AnnouncementRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $tenantId = \App\Support\Tenant::id();
        
        $stmt = $this->db->prepare('
            INSERT INTO announcements (
                tenant_id, author_id, title, content, target_audience,
                target_roles, target_users, priority, status, publish_at, expires_at
            ) VALUES (
                :tenant_id, :author_id, :title, :content, :target_audience,
                :target_roles, :target_users, :priority, :status, :publish_at, :expires_at
            )
        ');

        $stmt->execute([
            'tenant_id' => $tenantId,
            'author_id' => $data['author_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'] ?? 'all',
            'target_roles' => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'target_users' => !empty($data['target_users']) ? json_encode($data['target_users']) : null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['status'] ?? 'draft',
            'publish_at' => $data['publish_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = '
            SELECT 
                a.*,
                author.name AS author_name,
                author.email AS author_email
            FROM announcements a
            LEFT JOIN users author ON author.id = a.author_id
            WHERE a.id = :id
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (a.tenant_id IS NULL OR a.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $announcement = $stmt->fetch();
        if ($announcement) {
            if ($announcement['target_roles']) {
                $announcement['target_roles'] = json_decode($announcement['target_roles'], true);
            }
            if ($announcement['target_users']) {
                $announcement['target_users'] = json_decode($announcement['target_users'], true);
            }
        }

        return $announcement ?: null;
    }

    public function all(?string $status = null, ?int $userId = null, ?string $roleKey = null, int $limit = 100): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = '
            SELECT 
                a.*,
                author.name AS author_name';
        
        if ($userId) {
            $sql .= ',
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.user_id = :user_id) AS is_read';
        } else {
            $sql .= ',
                0 AS is_read';
        }
        
        $sql .= '
            FROM announcements a
            LEFT JOIN users author ON author.id = a.author_id
            WHERE 1 = 1
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (a.tenant_id IS NULL OR a.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($status) {
            $sql .= ' AND a.status = :status';
            $params['status'] = $status;
        } else {
            $sql .= ' AND a.status = \'published\'';
        }

        // Filter by publish date
        $sql .= ' AND (a.publish_at IS NULL OR a.publish_at <= NOW())';
        
        // Filter by expiry date
        $sql .= ' AND (a.expires_at IS NULL OR a.expires_at >= NOW())';

        // Filter by target audience
        if ($userId || $roleKey) {
            $conditions = ['a.target_audience = \'all\''];
            
            if ($roleKey) {
                $conditions[] = '(a.target_audience = \'roles\' AND JSON_CONTAINS(a.target_roles, :role_json))';
                $params['role_json'] = json_encode($roleKey);
            }
            
            if ($userId) {
                $conditions[] = '(a.target_audience = \'users\' AND JSON_CONTAINS(a.target_users, :user_json))';
                $params['user_json'] = json_encode($userId);
                $params['user_id'] = $userId;
            }
            
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        $sql .= ' ORDER BY 
            CASE a.priority
                WHEN \'urgent\' THEN 1
                WHEN \'high\' THEN 2
                WHEN \'normal\' THEN 3
                WHEN \'low\' THEN 4
            END,
            a.created_at DESC
        LIMIT :limit';
        $params['limit'] = $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $announcements = $stmt->fetchAll();
        foreach ($announcements as &$announcement) {
            if ($announcement['target_roles']) {
                $announcement['target_roles'] = json_decode($announcement['target_roles'], true);
            }
            if ($announcement['target_users']) {
                $announcement['target_users'] = json_decode($announcement['target_users'], true);
            }
        }

        return $announcements;
    }

    public function update(int $id, array $data): void
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'] ?? 'all',
            'target_roles' => !empty($data['target_roles']) ? json_encode($data['target_roles']) : null,
            'target_users' => !empty($data['target_users']) ? json_encode($data['target_users']) : null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['status'] ?? 'draft',
            'publish_at' => $data['publish_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ];

        $sql = '
            UPDATE announcements SET
                title = :title,
                content = :content,
                target_audience = :target_audience,
                target_roles = :target_roles,
                target_users = :target_users,
                priority = :priority,
                status = :status,
                publish_at = :publish_at,
                expires_at = :expires_at,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = 'DELETE FROM announcements WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function markAsRead(int $announcementId, int $userId): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO announcement_reads (announcement_id, user_id)
            VALUES (:announcement_id, :user_id)
            ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
        ');

        $stmt->execute([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
        ]);
    }

    public function getUnreadCount(?int $userId = null, ?string $roleKey = null): int
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = '
            SELECT COUNT(DISTINCT a.id)
            FROM announcements a
            WHERE a.status = \'published\'
            AND (a.publish_at IS NULL OR a.publish_at <= NOW())
            AND (a.expires_at IS NULL OR a.expires_at >= NOW())
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (a.tenant_id IS NULL OR a.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($userId || $roleKey) {
            $conditions = ['a.target_audience = \'all\''];
            
            if ($roleKey) {
                $conditions[] = '(a.target_audience = \'roles\' AND JSON_CONTAINS(a.target_roles, :role_json))';
                $params['role_json'] = json_encode($roleKey);
            }
            
            if ($userId) {
                $conditions[] = '(a.target_audience = \'users\' AND JSON_CONTAINS(a.target_users, :user_json))';
                $params['user_json'] = json_encode($userId);
            }
            
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        if ($userId) {
            $sql .= ' AND a.id NOT IN (SELECT announcement_id FROM announcement_reads WHERE user_id = :user_id)';
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}

