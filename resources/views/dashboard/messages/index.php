<?php
$pageTitle = 'Messages | Hotela';
$messages = $messages ?? [];
$unreadCount = $unreadCount ?? 0;
$folder = $folder ?? 'inbox';
$filters = $filters ?? ['status' => ''];

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="messages-header">
        <div>
            <h2>Messages</h2>
            <p class="messages-subtitle">
                <?php if ($folder === 'inbox'): ?>
                    <?php if ($unreadCount > 0): ?>
                        You have <strong><?= $unreadCount; ?></strong> unread message<?= $unreadCount !== 1 ? 's' : ''; ?>
                    <?php else: ?>
                        All caught up! No unread messages.
                    <?php endif; ?>
                <?php else: ?>
                    Sent Messages
                <?php endif; ?>
            </p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url('dashboard/messages/compose'); ?>" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    <line x1="9" y1="10" x2="15" y2="10"></line>
                </svg>
                Compose
            </a>
        </div>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="messages-tabs">
        <a href="<?= base_url('dashboard/messages?folder=inbox'); ?>" class="tab <?= $folder === 'inbox' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            Inbox
            <?php if ($folder === 'inbox' && $unreadCount > 0): ?>
                <span class="badge"><?= $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= base_url('dashboard/messages?folder=sent'); ?>" class="tab <?= $folder === 'sent' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
            Sent
        </a>
    </div>

    <div class="messages-section">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <h3>No messages found</h3>
                <p>You're all caught up! No messages in your <?= $folder; ?>.</p>
            </div>
        <?php else: ?>
            <div class="messages-list">
                <?php foreach ($messages as $message): ?>
                    <a href="<?= base_url('dashboard/messages/view?id=' . $message['id']); ?>" class="message-item <?= $message['status'] === 'sent' ? 'unread' : ''; ?> <?= $message['is_important'] ? 'important' : ''; ?>">
                        <div class="message-indicator"></div>
                        <div class="message-content">
                            <div class="message-header">
                                <div>
                                    <h4 class="message-subject"><?= htmlspecialchars($message['subject']); ?></h4>
                                    <p class="message-preview">
                                        <?php
                                        $preview = htmlspecialchars($message['message']);
                                        echo strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
                                        ?>
                                    </p>
                                </div>
                                <?php if ($message['is_important']): ?>
                                    <span class="badge badge-important">Important</span>
                                <?php endif; ?>
                            </div>
                            <div class="message-meta">
                                <span class="message-from">
                                    <?php if ($folder === 'inbox'): ?>
                                        From: <strong><?= htmlspecialchars($message['sender_name'] ?? 'Unknown'); ?></strong>
                                    <?php else: ?>
                                        To: <strong><?= htmlspecialchars($message['recipient_name'] ?? ($message['recipient_role'] ? ucfirst(str_replace('_', ' ', $message['recipient_role'])) : 'All')); ?></strong>
                                    <?php endif; ?>
                                </span>
                                <span class="message-time">
                                    <?php
                                    $createdAt = strtotime($message['created_at']);
                                    $now = time();
                                    $diff = $now - $createdAt;
                                    
                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . ' min ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hr ago';
                                    } elseif ($diff < 604800) {
                                        echo floor($diff / 86400) . ' day' . (floor($diff / 86400) !== 1 ? 's' : '') . ' ago';
                                    } else {
                                        echo date('M j, Y', $createdAt);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.messages-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.messages-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.messages-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.messages-subtitle strong {
    color: #8b5cf6;
    font-weight: 600;
}

.messages-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
}

.messages-tabs .tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    color: #64748b;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.messages-tabs .tab:hover {
    color: #475569;
}

.messages-tabs .tab.active {
    color: #8b5cf6;
    border-bottom-color: #8b5cf6;
}

.messages-tabs .tab .badge {
    background: #ef4444;
    color: #ffffff;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-weight: 600;
}

.messages-section {
    margin-top: 1rem;
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.message-item {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
    position: relative;
}

.message-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.message-item.unread {
    background: #f8fafc;
    border-left: 4px solid #8b5cf6;
}

.message-item.important {
    border-left-color: #ef4444;
}

.message-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.message-item.unread .message-indicator {
    background: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
}

.message-content {
    flex: 1;
    min-width: 0;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.message-subject {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
}

.message-preview {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: #64748b;
}

.message-from strong {
    color: #475569;
    font-weight: 600;
}

.message-time {
    color: #94a3b8;
}

.badge-important {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-state svg {
    margin: 0 auto 1.5rem;
    color: #cbd5e1;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .messages-header {
        flex-direction: column;
    }

    .messages-tabs {
        overflow-x: auto;
    }

    .message-item {
        padding: 1rem;
    }

    .message-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

