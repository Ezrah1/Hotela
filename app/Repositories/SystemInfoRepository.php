<?php

namespace App\Repositories;

use PDO;

class SystemInfoRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function get(string $key, $default = null)
    {
        // Check if table exists first
        $tableExists = $this->db->query("SHOW TABLES LIKE 'system_info'")->fetch();
        if (!$tableExists) {
            return $default;
        }

        $stmt = $this->db->prepare('SELECT info_value FROM system_info WHERE info_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        return $row ? $row['info_value'] : $default;
    }

    public function set(string $key, $value): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO system_info (info_key, info_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE info_value = VALUES(info_value)
        ');

        return $stmt->execute([$key, (string) $value]);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT info_key, info_value FROM system_info ORDER BY info_key');
        $rows = $stmt->fetchAll();

        $info = [];
        foreach ($rows as $row) {
            $info[$row['info_key']] = $row['info_value'];
        }

        return $info;
    }
}

