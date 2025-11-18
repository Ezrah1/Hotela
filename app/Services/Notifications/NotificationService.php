<?php

namespace App\Services\Notifications;

use App\Repositories\NotificationRepository;

class NotificationService
{
    protected NotificationRepository $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationRepository();
    }

    public function notifyRole(string $roleKey, string $title, string $message, array $payload = []): void
    {
        $this->notifications->create([
            'role_key' => $roleKey,
            'title' => $title,
            'message' => $message,
            'payload' => $payload ? json_encode($payload) : null,
        ]);
    }

    public function latestForRole(string $roleKey, int $limit = 10): array
    {
        return $this->notifications->latestForRole($roleKey, $limit);
    }
}

