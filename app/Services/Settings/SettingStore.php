<?php

namespace App\Services\Settings;

class SettingStore
{
    protected \PDO $db;
    protected ?int $tenantId;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
        $this->tenantId = \App\Support\Tenant::id();
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
            INSERT INTO settings (tenant_id, namespace, `key`, value)
            VALUES (:tenant_id, :namespace, :key, :value)
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP
        ');

        foreach ($values as $key => $value) {
            $stmt->execute([
                'tenant_id' => $this->tenantId,
                'namespace' => $group,
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_SLASHES),
            ]);
        }

        settings_set_cache(load_settings_cache(true));
    }
}


