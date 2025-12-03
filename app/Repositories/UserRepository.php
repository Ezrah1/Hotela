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

        if ($user) {
            // Load all roles for this user
            $user['roles'] = $this->getUserRoles($id);
            $user['role_keys'] = array_column($user['roles'], 'role_key');
            // Set primary role_key for backward compatibility
            $primaryRole = $this->getPrimaryRole($id);
            if ($primaryRole) {
                $user['role_key'] = $primaryRole;
            }
        }

        return $user ?: null;
    }

    /**
     * Get all roles assigned to a user
     */
    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT ur.role_key, ur.is_primary, r.name AS role_name
            FROM user_roles ur
            INNER JOIN roles r ON r.`key` = ur.role_key
            WHERE ur.user_id = :user_id
            ORDER BY ur.is_primary DESC, r.name ASC
        ');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get primary role for a user (for backward compatibility)
     */
    public function getPrimaryRole(int $userId): ?string
    {
        $stmt = $this->db->prepare('
            SELECT role_key
            FROM user_roles
            WHERE user_id = :user_id AND is_primary = 1
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ? $result['role_key'] : null;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(int $userId, string $roleKey): bool
    {
        $stmt = $this->db->prepare('
            SELECT 1
            FROM user_roles
            WHERE user_id = :user_id AND role_key = :role_key
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId, 'role_key' => $roleKey]);
        return (bool)$stmt->fetch();
    }

    /**
     * Set roles for a user (replaces all existing roles)
     */
    public function setUserRoles(int $userId, array $roleKeys, ?string $primaryRoleKey = null): void
    {
        // Remove existing roles
        $deleteStmt = $this->db->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        if (empty($roleKeys)) {
            return;
        }

        // If no primary role specified, use the first one
        if (!$primaryRoleKey && !empty($roleKeys)) {
            $primaryRoleKey = $roleKeys[0];
        }

        // Insert new roles
        $insertStmt = $this->db->prepare('
            INSERT INTO user_roles (user_id, role_key, is_primary)
            VALUES (:user_id, :role_key, :is_primary)
        ');

        foreach ($roleKeys as $roleKey) {
            $isPrimary = ($roleKey === $primaryRoleKey) ? 1 : 0;
            $insertStmt->execute([
                'user_id' => $userId,
                'role_key' => $roleKey,
                'is_primary' => $isPrimary,
            ]);
        }

        // Update users.role_key for backward compatibility
        if ($primaryRoleKey) {
            $updateStmt = $this->db->prepare('UPDATE users SET role_key = :role_key WHERE id = :user_id');
            $updateStmt->execute(['role_key' => $primaryRoleKey, 'user_id' => $userId]);
        }
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :identifier OR email = :identifier LIMIT 1');
        $stmt->execute(['identifier' => $identifier]);
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
            $conditions[] = '(users.name LIKE :search OR users.email LIKE :search OR users.username LIKE :search)';
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
        $allowedFields = ['name', 'email', 'username', 'role_key', 'status'];
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


