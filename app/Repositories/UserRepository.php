<?php

namespace App\Repositories;

use PDO;

class UserRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT users.*, roles.name AS role_name
            FROM users
            LEFT JOIN roles ON roles.`key` = users.role_key
            WHERE users.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function all(?string $roleFilter = null, ?string $statusFilter = null, ?string $search = null): array
    {
        $params = [];
        $conditions = [];

        // Role filter
        if ($roleFilter) {
            $params['role_key'] = $roleFilter;
            $conditions[] = 'users.role_key = :role_key';
        }

        // Status filter
        if ($statusFilter) {
            $params['status'] = $statusFilter;
            $conditions[] = 'users.status = :status';
        }

        // Search filter
        if ($search) {
            $params['search'] = '%' . $search . '%';
            $conditions[] = '(users.name LIKE :search OR users.email LIKE :search)';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT users.*, roles.name AS role_name
            FROM users
            LEFT JOIN roles ON roles.`key` = users.role_key
            {$whereClause}
            ORDER BY users.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function byDepartment(string $department): array
    {
        // Map department to role keys
        $departmentRoleMap = [
            'front_desk' => ['service_agent', 'receptionist'],
            'cashier' => ['cashier'],
            'service' => ['service_agent', 'receptionist'],
            'kitchen' => ['kitchen'],
            'housekeeping' => ['housekeeping'],
            'maintenance' => ['ground'],
            'security' => ['security'],
            'management' => ['director', 'operation_manager', 'finance_manager', 'admin', 'tech'],
        ];

        $roleKeys = $departmentRoleMap[$department] ?? [];
        
        if (empty($roleKeys)) {
            return [];
        }

        $params = [];
        $conditions = [];

        // Role filter - use IN clause for multiple roles
        $placeholders = [];
        foreach ($roleKeys as $index => $roleKey) {
            $param = 'role_key_' . $index;
            $params[$param] = $roleKey;
            $placeholders[] = ":{$param}";
        }
        $conditions[] = 'users.role_key IN (' . implode(', ', $placeholders) . ')';

        // Only active users
        $conditions[] = "users.status = 'active'";

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "
            SELECT users.id, users.name, users.email, users.role_key, roles.name AS role_name
            FROM users
            LEFT JOIN roles ON roles.`key` = users.role_key
            {$whereClause}
            ORDER BY users.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): void
    {
        $allowedFields = ['name', 'email', 'role_key', 'status'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}


