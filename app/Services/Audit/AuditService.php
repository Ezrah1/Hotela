<?php

namespace App\Services\Audit;

use App\Repositories\AuditLogRepository;

class AuditService
{
    protected AuditLogRepository $repository;

    public function __construct(?AuditLogRepository $repository = null)
    {
        $this->repository = $repository ?? new AuditLogRepository();
    }

    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): int {
        $user = null;
        $userId = null;
        $userName = null;
        $roleKey = null;
        
        try {
            if (\App\Support\Auth::check()) {
                $user = \App\Support\Auth::user();
                $userId = $user['id'] ?? null;
                $userName = $user['name'] ?? ($user['username'] ?? null);
                $roleKey = $user['role_key'] ?? ($user['role'] ?? null);
            }
        } catch (\RuntimeException $e) {
            // Not authenticated - use null values
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        return $this->repository->create([
            'user_id' => $userId,
            'user_name' => $userName,
            'role_key' => $roleKey,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function logCreate(string $entityType, int $entityId, array $values, ?string $description = null): int
    {
        return $this->log('create', $entityType, $entityId, $description, null, $values);
    }

    public function logUpdate(string $entityType, int $entityId, array $oldValues, array $newValues, ?string $description = null): int
    {
        return $this->log('update', $entityType, $entityId, $description, $oldValues, $newValues);
    }

    public function logDelete(string $entityType, int $entityId, array $values, ?string $description = null): int
    {
        return $this->log('delete', $entityType, $entityId, $description, $values, null);
    }

    public function logLogin(int $userId, string $userName, ?string $roleKey = null): int
    {
        return $this->log('login', 'user', $userId, "User {$userName} logged in", null, ['user_id' => $userId, 'user_name' => $userName]);
    }

    public function logLogout(int $userId, string $userName, ?string $roleKey = null): int
    {
        return $this->log('logout', 'user', $userId, "User {$userName} logged out", null, ['user_id' => $userId, 'user_name' => $userName]);
    }
}

