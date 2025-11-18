<?php

namespace App\Support;

use App\Repositories\TenantRepository;

class Tenant
{
    protected static ?array $current = null;

    public static function set(?array $tenant): void
    {
        self::$current = $tenant;
    }

    public static function current(): ?array
    {
        return self::$current;
    }

    public static function id(): ?int
    {
        return self::$current['id'] ?? null;
    }

    public static function resolveByDomain(string $domain): ?array
    {
        $repository = new TenantRepository();
        return $repository->findByDomain($domain);
    }

    public static function resolveById(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $repository = new TenantRepository();
        return $repository->find($id);
    }
}

