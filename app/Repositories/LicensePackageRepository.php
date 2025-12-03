<?php

namespace App\Repositories;

use PDO;

class LicensePackageRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_packages ORDER BY sort_order ASC, created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM license_packages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function active(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_packages WHERE is_active = 1 ORDER BY sort_order ASC, price ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_packages (name, description, price, currency, duration_months, features, is_active, sort_order)
            VALUES (:name, :description, :price, :currency, :duration_months, :features, :is_active, :sort_order)
        ');
        
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'USD',
            'duration_months' => $data['duration_months'] ?? 12,
            'features' => isset($data['features']) ? json_encode($data['features']) : null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        $allowed = ['name', 'description', 'price', 'currency', 'duration_months', 'features', 'is_active', 'sort_order'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                if ($field === 'features') {
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE license_packages SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM license_packages WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

