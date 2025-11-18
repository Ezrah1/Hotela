<?php

namespace App\Modules\Notifications\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\NotificationRepository;
use App\Support\Auth;

class NotificationsController extends Controller
{
    protected NotificationRepository $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $roleKey = $user['role_key'] ?? $user['role'] ?? null;
        $status = $request->input('status', '');
        $limit = $request->input('limit') ? (int)$request->input('limit') : 50;

        $notifications = $this->notifications->all($roleKey, $status ?: null, $limit);
        $unreadCount = $this->notifications->getUnreadCount($roleKey);

        $this->view('dashboard/notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
            ],
        ]);
    }

    public function markAsRead(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/notifications?error=' . urlencode('Invalid notification ID')));
            return;
        }

        try {
            $this->notifications->markAsRead($id);
            header('Location: ' . base_url('dashboard/notifications?success=' . urlencode('Notification marked as read')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/notifications?error=' . urlencode('Failed to mark notification as read')));
        }
    }

    public function markAllAsRead(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $roleKey = $user['role_key'] ?? $user['role'] ?? null;

        try {
            $count = $this->notifications->markAllAsRead($roleKey);
            header('Location: ' . base_url('dashboard/notifications?success=' . urlencode($count . ' notification(s) marked as read')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/notifications?error=' . urlencode('Failed to mark notifications as read')));
        }
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/notifications?error=' . urlencode('Invalid notification ID')));
            return;
        }

        try {
            $this->notifications->delete($id);
            header('Location: ' . base_url('dashboard/notifications?success=' . urlencode('Notification deleted')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/notifications?error=' . urlencode('Failed to delete notification')));
        }
    }
}

