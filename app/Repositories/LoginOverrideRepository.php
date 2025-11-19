<?php

namespace App\Repositories;

class LoginOverrideRepository
{
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    public function create(int $userId, int $approverId, ?string $reason = null, int $durationHours = 1): int
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hour"));

        $stmt = $this->db->prepare('
            INSERT INTO login_overrides (user_id, approver_id, reason, expires_at)
            VALUES (:user_id, :approver_id, :reason, :expires_at)
        ');

        $stmt->execute([
            'user_id' => $userId,
            'approver_id' => $approverId,
            'reason' => $reason,
            'expires_at' => $expiresAt,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getActiveOverride(int $userId): ?array
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            SELECT lo.*, u.name as approver_name
            FROM login_overrides lo
            INNER JOIN users u ON u.id = lo.approver_id
            WHERE lo.user_id = :user_id
            AND lo.expires_at > :now
            AND lo.used_at IS NULL
            ORDER BY lo.created_at DESC
            LIMIT 1
        ');

        $stmt->execute([
            'user_id' => $userId,
            'now' => $now,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function markAsUsed(int $overrideId): void
    {
        $stmt = $this->db->prepare('
            UPDATE login_overrides
            SET used_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute(['id' => $overrideId]);
    }

    public function getOverridesForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('
            SELECT lo.*, u.name as approver_name
            FROM login_overrides lo
            INNER JOIN users u ON u.id = lo.approver_id
            WHERE lo.user_id = :user_id
            ORDER BY lo.created_at DESC
            LIMIT :limit
        ');

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getOverridesByApprover(int $approverId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('
            SELECT lo.*, u.name as user_name, u.email as user_email
            FROM login_overrides lo
            INNER JOIN users u ON u.id = lo.user_id
            WHERE lo.approver_id = :approver_id
            ORDER BY lo.created_at DESC
            LIMIT :limit
        ');

        $stmt->bindValue(':approver_id', $approverId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

