<?php

namespace App\Modules\Messages\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;

class MessagesController extends Controller
{
    protected MessageRepository $messages;
    protected UserRepository $users;

    public function __construct()
    {
        $this->messages = new MessageRepository();
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $folder = $request->input('folder', 'inbox'); // inbox, sent
        $status = $request->input('status', '');

        if ($folder === 'sent') {
            $messages = $this->messages->getSent($user['id'], 50);
        } else {
            $messages = $this->messages->getInbox($user['id'], $status ?: null, 50);
        }

        $unreadCount = $this->messages->getUnreadCount($user['id'], $user['role_key'] ?? $user['role'] ?? null);

        $this->view('dashboard/messages/index', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'folder' => $folder,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function compose(Request $request): void
    {
        Auth::requireRoles();

        if ($request->method() === 'POST') {
            $this->send($request);
            return;
        }

        $recipientId = $request->input('to') ? (int)$request->input('to') : null;
        $recipientRole = $request->input('role') ?: null;

        $this->view('dashboard/messages/compose', [
            'users' => $this->users->all(),
            'recipientId' => $recipientId,
            'recipientRole' => $recipientRole,
        ]);
    }

    public function send(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/messages/compose?error=' . urlencode('User not authenticated')));
            return;
        }

        $subject = trim($request->input('subject', ''));
        $message = trim($request->input('message', ''));
        $recipientId = $request->input('recipient_id') ? (int)$request->input('recipient_id') : null;
        $recipientRole = $request->input('recipient_role') ?: null;
        $isImportant = $request->input('is_important') ? 1 : 0;

        if (empty($subject) || empty($message)) {
            header('Location: ' . base_url('dashboard/messages/compose?error=' . urlencode('Subject and message are required')));
            return;
        }

        if (!$recipientId && !$recipientRole) {
            header('Location: ' . base_url('dashboard/messages/compose?error=' . urlencode('Please select a recipient')));
            return;
        }

        try {
            $this->messages->create([
                'sender_id' => $user['id'],
                'recipient_id' => $recipientId,
                'recipient_role' => $recipientRole,
                'subject' => $subject,
                'message' => $message,
                'is_important' => $isImportant,
            ]);

            header('Location: ' . base_url('dashboard/messages?success=' . urlencode('Message sent successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/messages/compose?error=' . urlencode('Failed to send message: ' . $e->getMessage())));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/messages?error=' . urlencode('Invalid message ID')));
            return;
        }

        $user = Auth::user();
        $message = $this->messages->find($id);

        if (!$message) {
            header('Location: ' . base_url('dashboard/messages?error=' . urlencode('Message not found')));
            return;
        }

        // Check if user has access to this message
        if ($message['sender_id'] != $user['id'] && $message['recipient_id'] != $user['id']) {
            header('Location: ' . base_url('dashboard/messages?error=' . urlencode('Access denied')));
            return;
        }

        // Mark as read if user is recipient
        if ($message['recipient_id'] == $user['id'] && $message['status'] === 'sent') {
            $this->messages->markAsRead($id, $user['id']);
            $message['status'] = 'read';
        }

        $this->view('dashboard/messages/view', [
            'message' => $message,
        ]);
    }

    public function markAsRead(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        $user = Auth::user();

        if ($id) {
            $this->messages->markAsRead($id, $user['id']);
        }

        header('Location: ' . base_url('dashboard/messages?success=' . urlencode('Message marked as read')));
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles();

        $id = (int)$request->input('id');
        $user = Auth::user();

        if ($id) {
            $this->messages->delete($id, $user['id']);
        }

        header('Location: ' . base_url('dashboard/messages?success=' . urlencode('Message deleted')));
    }
}

