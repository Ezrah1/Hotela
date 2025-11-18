<?php
$pageTitle = 'View Message | Hotela';
$message = $message ?? null;

if (!$message) {
    header('Location: ' . base_url('dashboard/messages?error=' . urlencode('Message not found')));
    exit;
}

ob_start();
?>
<section class="card">
    <header class="message-view-header">
        <a href="<?= base_url('dashboard/messages'); ?>" class="btn btn-outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back
        </a>
        <div class="header-actions">
            <?php if ($message['status'] === 'sent'): ?>
                <a href="<?= base_url('dashboard/messages/mark-read?id=' . $message['id']); ?>" class="btn btn-outline">
                    Mark as Read
                </a>
            <?php endif; ?>
            <a href="<?= base_url('dashboard/messages/delete?id=' . $message['id']); ?>" class="btn btn-outline btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">
                Delete
            </a>
        </div>
    </header>

    <div class="message-view">
        <div class="message-view-header-info">
            <h2><?= htmlspecialchars($message['subject']); ?></h2>
            <?php if ($message['is_important']): ?>
                <span class="badge badge-important">Important</span>
            <?php endif; ?>
        </div>

        <div class="message-view-meta">
            <div class="meta-item">
                <strong>From:</strong>
                <span><?= htmlspecialchars($message['sender_name'] ?? 'Unknown'); ?></span>
                <small><?= htmlspecialchars($message['sender_email'] ?? ''); ?></small>
            </div>
            <div class="meta-item">
                <strong>To:</strong>
                <span>
                    <?php if ($message['recipient_id']): ?>
                        <?= htmlspecialchars($message['recipient_name'] ?? 'Unknown'); ?>
                        <small><?= htmlspecialchars($message['recipient_email'] ?? ''); ?></small>
                    <?php else: ?>
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $message['recipient_role']))); ?> (All)
                    <?php endif; ?>
                </span>
            </div>
            <div class="meta-item">
                <strong>Date:</strong>
                <span><?= date('F j, Y g:i A', strtotime($message['created_at'])); ?></span>
            </div>
            <div class="meta-item">
                <strong>Status:</strong>
                <span class="status-badge status-<?= $message['status']; ?>">
                    <?= ucfirst($message['status']); ?>
                </span>
            </div>
        </div>

        <div class="message-view-content">
            <div class="message-body">
                <?= nl2br(htmlspecialchars($message['message'])); ?>
            </div>
        </div>
    </div>
</section>

<style>
.message-view-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.message-view-header-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.message-view-header-info h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.message-view-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.meta-item strong {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.meta-item span {
    color: #1e293b;
    font-weight: 500;
}

.meta-item small {
    color: #64748b;
    font-size: 0.875rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-sent {
    background: #dbeafe;
    color: #1e40af;
}

.status-read {
    background: #d1fae5;
    color: #065f46;
}

.status-archived {
    background: #f3f4f6;
    color: #374151;
}

.message-view-content {
    padding: 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
}

.message-body {
    color: #1e293b;
    line-height: 1.7;
    font-size: 1rem;
    white-space: pre-wrap;
}

.badge-important {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.375rem 0.875rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.btn-danger {
    color: #ef4444;
    border-color: #fecaca;
}

.btn-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}

@media (max-width: 768px) {
    .message-view-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .message-view-meta {
        grid-template-columns: 1fr;
    }

    .message-view-content {
        padding: 1.5rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

