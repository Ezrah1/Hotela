<?php

namespace App\Modules\Announcements\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AnnouncementRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;

class AnnouncementsController extends Controller
{
    protected AnnouncementRepository $announcements;
    protected UserRepository $users;

    public function __construct()
    {
        $this->announcements = new AnnouncementRepository();
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $status = $request->input('status', 'published');

        $announcements = $this->announcements->all(
            $status === 'all' ? null : $status,
            $user['id'],
            $user['role_key'] ?? $user['role'] ?? null,
            100
        );

        $unreadCount = $this->announcements->getUnreadCount($user['id'], $user['role_key'] ?? $user['role'] ?? null);

        $this->view('dashboard/announcements/index', [
            'announcements' => $announcements,
            'unreadCount' => $unreadCount,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        if ($request->method() === 'POST') {
            $this->store($request);
            return;
        }

        $this->view('dashboard/announcements/create', [
            'users' => $this->users->all(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/announcements/create?error=' . urlencode('User not authenticated')));
            return;
        }

        $title = trim($request->input('title', ''));
        $content = trim($request->input('content', ''));
        $targetAudience = $request->input('target_audience', 'all');
        $priority = $request->input('priority', 'normal');
        $status = $request->input('status', 'draft');
        $publishAt = $this->sanitizeDateTime($request->input('publish_at'));
        $expiresAt = $this->sanitizeDateTime($request->input('expires_at'));

        if (empty($title) || empty($content)) {
            header('Location: ' . base_url('dashboard/announcements/create?error=' . urlencode('Title and content are required')));
            return;
        }

        $data = [
            'author_id' => $user['id'],
            'title' => $title,
            'content' => $content,
            'target_audience' => $targetAudience,
            'priority' => $priority,
            'status' => $status,
            'publish_at' => $publishAt,
            'expires_at' => $expiresAt,
        ];

        if ($targetAudience === 'roles') {
            $targetRoles = $request->input('target_roles', []);
            $data['target_roles'] = is_array($targetRoles) ? $targetRoles : [];
        } elseif ($targetAudience === 'users') {
            $targetUsers = $request->input('target_users', []);
            $data['target_users'] = is_array($targetUsers) ? array_map('intval', $targetUsers) : [];
        }

        try {
            $this->announcements->create($data);
            header('Location: ' . base_url('dashboard/announcements?success=' . urlencode('Announcement created successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/announcements/create?error=' . urlencode('Failed to create announcement: ' . $e->getMessage())));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Invalid announcement ID')));
            return;
        }

        $user = Auth::user();
        $announcement = $this->announcements->find($id);

        if (!$announcement) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Announcement not found')));
            return;
        }

        // Mark as read
        $this->announcements->markAsRead($id, $user['id']);

        $this->view('dashboard/announcements/view', [
            'announcement' => $announcement,
        ]);
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Invalid announcement ID')));
            return;
        }

        if ($request->method() === 'POST') {
            $this->update($request);
            return;
        }

        $announcement = $this->announcements->find($id);
        if (!$announcement) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Announcement not found')));
            return;
        }

        $this->view('dashboard/announcements/edit', [
            'announcement' => $announcement,
            'users' => $this->users->all(),
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Invalid announcement ID')));
            return;
        }

        $title = trim($request->input('title', ''));
        $content = trim($request->input('content', ''));
        $targetAudience = $request->input('target_audience', 'all');
        $priority = $request->input('priority', 'normal');
        $status = $request->input('status', 'draft');
        $publishAt = $this->sanitizeDateTime($request->input('publish_at'));
        $expiresAt = $this->sanitizeDateTime($request->input('expires_at'));

        if (empty($title) || empty($content)) {
            header('Location: ' . base_url('dashboard/announcements/edit?id=' . $id . '&error=' . urlencode('Title and content are required')));
            return;
        }

        $data = [
            'title' => $title,
            'content' => $content,
            'target_audience' => $targetAudience,
            'priority' => $priority,
            'status' => $status,
            'publish_at' => $publishAt,
            'expires_at' => $expiresAt,
        ];

        if ($targetAudience === 'roles') {
            $targetRoles = $request->input('target_roles', []);
            $data['target_roles'] = is_array($targetRoles) ? $targetRoles : [];
        } elseif ($targetAudience === 'users') {
            $targetUsers = $request->input('target_users', []);
            $data['target_users'] = is_array($targetUsers) ? array_map('intval', $targetUsers) : [];
        }

        try {
            $this->announcements->update($id, $data);
            header('Location: ' . base_url('dashboard/announcements?success=' . urlencode('Announcement updated successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/announcements/edit?id=' . $id . '&error=' . urlencode('Failed to update announcement: ' . $e->getMessage())));
        }
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Invalid announcement ID')));
            return;
        }

        try {
            $this->announcements->delete($id);
            header('Location: ' . base_url('dashboard/announcements?success=' . urlencode('Announcement deleted')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Failed to delete announcement')));
        }
    }

    protected function sanitizeDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}

