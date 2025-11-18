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

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE domain = :domain LIMIT 1');
        $stmt->execute(['domain' => $domain]);
        $tenant = $stmt->fetch();

        return $tenant ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $tenant = $stmt->fetch();

        return $tenant ?: null;
    }
}

