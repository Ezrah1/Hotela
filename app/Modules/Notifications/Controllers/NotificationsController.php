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
            header('Location: ' . base_url('staff/dashboard/notifications?error=' . urlencode('Invalid notification ID')));
            return;
        }

        try {
            $this->notifications->markAsRead($id);
            header('Location: ' . base_url('staff/dashboard/notifications?success=' . urlencode('Notification marked as read')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/notifications?error=' . urlencode('Failed to mark notification as read')));
        }
    }

    public function markAllAsRead(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $roleKey = $user['role_key'] ?? $user['role'] ?? null;

        try {
            $count = $this->notifications->markAllAsRead($roleKey);
            header('Location: ' . base_url('staff/dashboard/notifications?success=' . urlencode($count . ' notification(s) marked as read')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/notifications?error=' . urlencode('Failed to mark notifications as read')));
        }
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('staff/dashboard/notifications?error=' . urlencode('Invalid notification ID')));
            return;
        }

        try {
            $this->notifications->delete($id);
            header('Location: ' . base_url('staff/dashboard/notifications?success=' . urlencode('Notification deleted')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/notifications?error=' . urlencode('Failed to delete notification')));
        }
    }

    public function check(Request $request): void
    {
        Auth::requireRoles();
        header('Content-Type: application/json');

        $user = Auth::user();
        $roleKey = $user['role_key'] ?? $user['role'] ?? null;
        $lastCheck = $request->input('last_check');
        
        // Get unread count
        $unreadCount = $this->notifications->getUnreadCount($roleKey);
        
        // Get latest notifications if we have a timestamp
        $newNotifications = [];
        if ($lastCheck) {
            $allNotifications = $this->notifications->all($roleKey, 'unread', 10);
            foreach ($allNotifications as $notification) {
                if (strtotime($notification['created_at']) > (int)$lastCheck) {
                    $newNotifications[] = [
                        'id' => (int)$notification['id'],
                        'title' => $notification['title'],
                        'message' => $notification['message'],
                        'created_at' => $notification['created_at'],
                    ];
                }
            }
        }

        echo json_encode([
            'unread_count' => $unreadCount,
            'new_notifications' => $newNotifications,
            'timestamp' => time(),
        ]);
    }
}

