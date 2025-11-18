<?php

namespace App\Repositories;

use PDO;

class NotificationRepository
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
            INSERT INTO notifications (tenant_id, role_key, user_id, title, message, payload, status)
            VALUES (:tenant_id, :role_key, :user_id, :title, :message, :payload, :status)
        ');

        $stmt->execute([
            'tenant_id' => $tenantId,
            'role_key' => $data['role_key'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'payload' => $data['payload'] ?? null,
            'status' => $data['status'] ?? 'unread',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function latestForRole(string $roleKey, int $limit = 10): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'role' => $roleKey,
            'limit' => $limit,
        ];

        $sql = '
            SELECT * FROM notifications
            WHERE (role_key IS NULL OR role_key = :role)
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function all(?string $roleKey = null, ?string $status = null, int $limit = 100): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];
        $sql = '
            SELECT 
                n.*,
                u.name AS user_name
            FROM notifications n
            LEFT JOIN users u ON u.id = n.user_id
            WHERE 1 = 1
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (n.tenant_id IS NULL OR n.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($roleKey) {
            $sql .= ' AND (n.role_key IS NULL OR n.role_key = :role_key)';
            $params['role_key'] = $roleKey;
        }

        if ($status) {
            $sql .= ' AND n.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY n.created_at DESC LIMIT :limit';
        $params['limit'] = $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = '
            SELECT 
                n.*,
                u.name AS user_name
            FROM notifications n
            LEFT JOIN users u ON u.id = n.user_id
            WHERE n.id = :id
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (n.tenant_id IS NULL OR n.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function markAsRead(int $id): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'read_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'UPDATE notifications SET status = \'read\', read_at = :read_at WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(?string $roleKey = null): int
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'read_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'UPDATE notifications SET status = \'read\', read_at = :read_at WHERE status = \'unread\'';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($roleKey) {
            $sql .= ' AND (role_key IS NULL OR role_key = :role_key)';
            $params['role_key'] = $roleKey;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function delete(int $id): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = 'DELETE FROM notifications WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function getUnreadCount(?string $roleKey = null): int
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = 'SELECT COUNT(*) FROM notifications WHERE status = \'unread\'';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($roleKey) {
            $sql .= ' AND (role_key IS NULL OR role_key = :role_key)';
            $params['role_key'] = $roleKey;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}


