<?php

namespace App\Repositories;

use PDO;

class MessageRepository
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
            INSERT INTO messages (
                tenant_id, sender_id, recipient_id, recipient_role,
                subject, message, status, is_important
            ) VALUES (
                :tenant_id, :sender_id, :recipient_id, :recipient_role,
                :subject, :message, :status, :is_important
            )
        ');

        $stmt->execute([
            'tenant_id' => $tenantId,
            'sender_id' => $data['sender_id'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'recipient_role' => $data['recipient_role'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => $data['status'] ?? 'sent',
            'is_important' => $data['is_important'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = '
            SELECT 
                m.*,
                sender.name AS sender_name,
                sender.email AS sender_email,
                recipient.name AS recipient_name,
                recipient.email AS recipient_email
            FROM messages m
            LEFT JOIN users sender ON sender.id = m.sender_id
            LEFT JOIN users recipient ON recipient.id = m.recipient_id
            WHERE m.id = :id
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (m.tenant_id IS NULL OR m.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function getInbox(int $userId, ?string $status = null, int $limit = 50): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['user_id' => $userId];

        $sql = '
            SELECT 
                m.*,
                sender.name AS sender_name,
                sender.email AS sender_email
            FROM messages m
            LEFT JOIN users sender ON sender.id = m.sender_id
            WHERE (m.recipient_id = :user_id OR (m.recipient_role IS NOT NULL AND m.recipient_id IS NULL))
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (m.tenant_id IS NULL OR m.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        if ($status) {
            $sql .= ' AND m.status = :status';
            $params['status'] = $status;
        } else {
            $sql .= ' AND m.status != \'deleted\'';
        }

        $sql .= ' ORDER BY m.created_at DESC LIMIT :limit';
        $params['limit'] = $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getSent(int $userId, int $limit = 50): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['user_id' => $userId, 'limit' => $limit];

        $sql = '
            SELECT 
                m.*,
                recipient.name AS recipient_name,
                recipient.email AS recipient_email
            FROM messages m
            LEFT JOIN users recipient ON recipient.id = m.recipient_id
            WHERE m.sender_id = :user_id AND m.status != \'deleted\'
        ';

        if ($tenantId !== null) {
            $sql .= ' AND (m.tenant_id IS NULL OR m.tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' ORDER BY m.created_at DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'user_id' => $userId,
            'read_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'UPDATE messages SET status = \'read\', read_at = :read_at WHERE id = :id AND recipient_id = :user_id';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'user_id' => $userId,
            'deleted_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'UPDATE messages SET status = \'deleted\', deleted_at = :deleted_at WHERE id = :id AND (sender_id = :user_id OR recipient_id = :user_id)';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function getUnreadCount(int $userId, ?string $roleKey = null): int
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['user_id' => $userId];

        $sql = 'SELECT COUNT(*) FROM messages WHERE status = \'sent\' AND (recipient_id = :user_id';

        if ($roleKey) {
            $sql .= ' OR (recipient_role = :role_key AND recipient_id IS NULL)';
            $params['role_key'] = $roleKey;
        }

        $sql .= ')';

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id IS NULL OR tenant_id = :tenant_id)';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}

