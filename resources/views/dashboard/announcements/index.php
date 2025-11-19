<?php
$pageTitle = 'Announcements | Hotela';
$announcements = $announcements ?? [];
$unreadCount = $unreadCount ?? 0;
$filters = $filters ?? ['status' => 'published'];

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

$user = \App\Support\Auth::user();
$canCreate = in_array($user['role'] ?? '', ['admin', 'finance_manager', 'operation_manager']);

ob_start();
?>
<section class="card">
    <header class="announcements-header">
        <div>
            <h2>Announcements</h2>
            <p class="announcements-subtitle">
                <?php if ($unreadCount > 0): ?>
                    You have <strong><?= $unreadCount; ?></strong> unread announcement<?= $unreadCount !== 1 ? 's' : ''; ?>
                <?php else: ?>
                    All caught up! No unread announcements.
                <?php endif; ?>
            </p>
        </div>
        <div class="header-actions">
            <?php if ($canCreate): ?>
                <a href="<?= base_url('staff/dashboard/announcements/create'); ?>" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create Announcement
                </a>
            <?php endif; ?>
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

    <div class="announcements-section">
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>No announcements found</h3>
                <p>You're all caught up! No announcements match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="announcements-list">
                <?php foreach ($announcements as $announcement): ?>
                    <a href="<?= base_url('staff/dashboard/announcements/view?id=' . $announcement['id']); ?>" class="announcement-item <?= !$announcement['is_read'] ? 'unread' : ''; ?> priority-<?= $announcement['priority']; ?>">
                        <div class="announcement-indicator"></div>
                        <div class="announcement-content">
                            <div class="announcement-header">
                                <div>
                                    <h4 class="announcement-title"><?= htmlspecialchars($announcement['title']); ?></h4>
                                    <p class="announcement-preview">
                                        <?php
                                        $preview = htmlspecialchars(strip_tags($announcement['content']));
                                        echo strlen($preview) > 150 ? substr($preview, 0, 150) . '...' : $preview;
                                        ?>
                                    </p>
                                </div>
                                <div class="announcement-badges">
                                    <span class="badge badge-priority badge-<?= $announcement['priority']; ?>">
                                        <?= ucfirst($announcement['priority']); ?>
                                    </span>
                                    <?php if (!$announcement['is_read']): ?>
                                        <span class="badge badge-unread">New</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="announcement-meta">
                                <span class="announcement-author">
                                    By: <strong><?= htmlspecialchars($announcement['author_name'] ?? 'Unknown'); ?></strong>
                                </span>
                                <span class="announcement-time">
                                    <?php
                                    $createdAt = strtotime($announcement['created_at']);
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
.announcements-header {
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

.announcements-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.announcements-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.announcements-subtitle strong {
    color: #8b5cf6;
    font-weight: 600;
}

.announcements-section {
    margin-top: 1rem;
}

.announcements-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.announcement-item {
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

.announcement-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.announcement-item.unread {
    background: #f8fafc;
    border-left: 4px solid #8b5cf6;
}

.announcement-item.priority-urgent {
    border-left-color: #ef4444;
}

.announcement-item.priority-high {
    border-left-color: #f59e0b;
}

.announcement-item.priority-normal {
    border-left-color: #3b82f6;
}

.announcement-item.priority-low {
    border-left-color: #10b981;
}

.announcement-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.announcement-item.unread .announcement-indicator {
    background: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
}

.announcement-content {
    flex: 1;
    min-width: 0;
}

.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.announcement-title {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
}

.announcement-preview {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
}

.announcement-badges {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.badge-priority {
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-urgent {
    background: #fef2f2;
    color: #dc2626;
}

.badge-high {
    background: #fffbeb;
    color: #d97706;
}

.badge-normal {
    background: #eff6ff;
    color: #2563eb;
}

.badge-low {
    background: #f0fdf4;
    color: #16a34a;
}

.badge-unread {
    background: #8b5cf6;
    color: #ffffff;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.announcement-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: #64748b;
}

.announcement-author strong {
    color: #475569;
    font-weight: 600;
}

.announcement-time {
    color: #94a3b8;
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
    .announcements-header {
        flex-direction: column;
    }

    .announcement-item {
        padding: 1rem;
    }

    .announcement-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

