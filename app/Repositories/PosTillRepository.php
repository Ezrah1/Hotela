<?php

namespace App\Repositories;

use PDO;

class PosTillRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM pos_tills WHERE active = 1 ORDER BY name');
        return $stmt->fetchAll();
    }
}

