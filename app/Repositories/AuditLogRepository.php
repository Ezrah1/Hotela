<?php

namespace App\Repositories;

use PDO;

class AuditLogRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(array $data): int
    {
        $sql = '
            INSERT INTO audit_logs (
                tenant_id, user_id, user_name, role_key, action, entity_type, entity_id,
                description, old_values, new_values, ip_address, user_agent
            ) VALUES (
                :tenant_id, :user_id, :user_name, :role_key, :action, :entity_type, :entity_id,
                :description, :old_values, :new_values, :ip_address, :user_agent
            )
        ';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'role_key' => $data['role_key'] ?? null,
            'action' => $data['action'] ?? 'unknown',
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'] ?? null,
            'old_values' => !empty($data['old_values']) ? json_encode($data['old_values']) : null,
            'new_values' => !empty($data['new_values']) ? json_encode($data['new_values']) : null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function search(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = [];
        $params = [];
        
        // Date range filter (supports both date and datetime)
        if (!empty($filters['start_date'])) {
            // Check if it's a datetime string (contains time)
            if (strlen($filters['start_date']) > 10) {
                $where[] = 'created_at >= :start_date';
                $params['start_date'] = $filters['start_date'];
            } else {
                $where[] = 'DATE(created_at) >= :start_date';
                $params['start_date'] = $filters['start_date'];
            }
        }
        
        if (!empty($filters['end_date'])) {
            // Check if it's a datetime string (contains time)
            if (strlen($filters['end_date']) > 10) {
                $where[] = 'created_at <= :end_date';
                $params['end_date'] = $filters['end_date'];
            } else {
                $where[] = 'DATE(created_at) <= :end_date';
                $params['end_date'] = $filters['end_date'];
            }
        }
        
        // User filter
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = (int)$filters['user_id'];
        }
        
        // Role filter
        if (!empty($filters['role_key'])) {
            $where[] = 'role_key = :role_key';
            $params['role_key'] = $filters['role_key'];
        }
        
        // Action filter
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        
        // Entity type filter
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }
        
        // Search term (searches in description, user_name, action)
        if (!empty($filters['search'])) {
            $where[] = '(description LIKE :search OR user_name LIKE :search OR action LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT 
                id, tenant_id, user_id, user_name, role_key, action, entity_type, entity_id,
                description, old_values, new_values, ip_address, user_agent, created_at
            FROM audit_logs
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($logs as &$log) {
            if ($log['old_values']) {
                $log['old_values'] = json_decode($log['old_values'], true);
            }
            if ($log['new_values']) {
                $log['new_values'] = json_decode($log['new_values'], true);
            }
        }
        
        return $logs;
    }

    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];
        
        // Same filters as search (supports both date and datetime)
        if (!empty($filters['start_date'])) {
            // Check if it's a datetime string (contains time)
            if (strlen($filters['start_date']) > 10) {
                $where[] = 'created_at >= :start_date';
                $params['start_date'] = $filters['start_date'];
            } else {
                $where[] = 'DATE(created_at) >= :start_date';
                $params['start_date'] = $filters['start_date'];
            }
        }
        
        if (!empty($filters['end_date'])) {
            // Check if it's a datetime string (contains time)
            if (strlen($filters['end_date']) > 10) {
                $where[] = 'created_at <= :end_date';
                $params['end_date'] = $filters['end_date'];
            } else {
                $where[] = 'DATE(created_at) <= :end_date';
                $params['end_date'] = $filters['end_date'];
            }
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['role_key'])) {
            $where[] = 'role_key = :role_key';
            $params['role_key'] = $filters['role_key'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(description LIKE :search OR user_name LIKE :search OR action LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM audit_logs {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    public function getDistinctActions(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT action FROM audit_logs ORDER BY action');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctEntityTypes(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctRoles(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT role_key FROM audit_logs WHERE role_key IS NOT NULL AND role_key != 'admin' AND role_key != 'super_admin' ORDER BY role_key");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getUsers(): array
    {
        $stmt = $this->db->query('
            SELECT DISTINCT user_id, user_name 
            FROM audit_logs 
            WHERE user_id IS NOT NULL 
            ORDER BY user_name
        ');
        return $stmt->fetchAll();
    }
}

