<?php

namespace App\Repositories;

use PDO;

class SystemSettingsRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function get(string $key, $default = null)
    {
        // Check if table exists first
        $tableExists = $this->db->query("SHOW TABLES LIKE 'system_settings'")->fetch();
        if (!$tableExists) {
            return $default;
        }

        $stmt = $this->db->prepare('SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) {
            return $default;
        }

        return $this->castValue($row['setting_value'], $row['setting_type']);
    }

    public function set(string $key, $value, string $type = 'string', ?string $description = null): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                description = COALESCE(VALUES(description), description)
        ');

        $stringValue = $this->stringifyValue($value, $type);
        return $stmt->execute([$key, $stringValue, $type, $description]);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value, setting_type, description FROM system_settings ORDER BY setting_key');
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'type' => $row['setting_type'],
                'description' => $row['description'],
            ];
        }

        return $settings;
    }

    public function getGroup(string $prefix): array
    {
        $stmt = $this->db->prepare('SELECT setting_key, setting_value, setting_type FROM system_settings WHERE setting_key LIKE ? ORDER BY setting_key');
        $stmt->execute([$prefix . '%']);
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $key = str_replace($prefix . '_', '', $row['setting_key']);
            $settings[$key] = $this->castValue($row['setting_value'], $row['setting_type']);
        }

        return $settings;
    }

    protected function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'decimal', 'float', 'double' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    protected function stringifyValue($value, string $type): string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean', 'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}

