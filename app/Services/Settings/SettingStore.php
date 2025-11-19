<?php

namespace App\Services\Settings;

class SettingStore
{
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function all(): array
    {
        return load_settings_cache();
    }

    public function group(string $group): array
    {
        $all = $this->all();

        return $all[$group] ?? [];
    }

    public function updateGroup(string $group, array $values): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO settings (namespace, `key`, value)
            VALUES (:namespace, :key, :value)
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP
        ');

        foreach ($values as $key => $value) {
            $stmt->execute([
                'namespace' => $group,
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_SLASHES),
            ]);
        }

        settings_set_cache(load_settings_cache(true));
    }
}


