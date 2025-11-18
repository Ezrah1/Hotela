<?php

namespace App\Repositories;

use PDO;

class ExpenseCategoryRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(?string $department = null): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = 'SELECT * FROM expense_categories WHERE 1 = 1';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        if ($department) {
            $sql .= ' AND department = :department';
            $params['department'] = $department;
        }

        $sql .= ' ORDER BY department, name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        $sql = 'SELECT * FROM expense_categories WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO expense_categories (tenant_id, name, description, department, is_petty_cash)
            VALUES (:tenant_id, :name, :description, :department, :is_petty_cash)
        ');

        $stmt->execute([
            'tenant_id' => \App\Support\Tenant::id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'department' => $data['department'] ?? null,
            'is_petty_cash' => $data['is_petty_cash'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'department' => $data['department'] ?? null,
            'is_petty_cash' => $data['is_petty_cash'] ?? 0,
        ];

        $sql = 'UPDATE expense_categories SET name = :name, description = :description, department = :department, is_petty_cash = :is_petty_cash WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function getPettyCashCategories(): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = 'SELECT * FROM expense_categories WHERE is_petty_cash = 1';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' ORDER BY name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $tenantId = \App\Support\Tenant::id();
        $params = ['id' => $id];

        // Check if category is in use
        $checkStmt = $this->db->prepare('SELECT COUNT(*) as count FROM expenses WHERE category_id = :id');
        $checkStmt->execute(['id' => $id]);
        $result = $checkStmt->fetch();

        if ($result && (int)$result['count'] > 0) {
            return false; // Cannot delete category in use
        }

        $sql = 'DELETE FROM expense_categories WHERE id = :id';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }
}

