<?php
$pageTitle = 'Notifications | Hotela';
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;
$filters = $filters ?? ['status' => '', 'limit' => 50];

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="notifications-header">
        <div>
            <h2>Notifications</h2>
            <p class="notifications-subtitle">
                <?php if ($unreadCount > 0): ?>
                    You have <strong><?= $unreadCount; ?></strong> unread notification<?= $unreadCount !== 1 ? 's' : ''; ?>
                <?php else: ?>
                    All caught up! No unread notifications.
                <?php endif; ?>
            </p>
        </div>
        <div class="header-actions">
            <?php if ($unreadCount > 0): ?>
                <a href="<?= base_url('staff/dashboard/notifications/mark-all-read'); ?>" class="btn btn-outline">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Mark All as Read
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

    <form method="get" action="<?= base_url('staff/dashboard/notifications'); ?>" class="notifications-filters">
        <div class="filter-grid">
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Notifications</option>
                    <option value="unread" <?= $filters['status'] === 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                    <option value="read" <?= $filters['status'] === 'read' ? 'selected' : ''; ?>>Read Only</option>
                </select>
            </label>
            <label>
                <span>Limit</span>
                <select name="limit" class="modern-select">
                    <option value="25" <?= $filters['limit'] == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= $filters['limit'] == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= $filters['limit'] == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/notifications'); ?>">Reset</a>
            </div>
        </div>
    </form>

    <!-- Notifications List -->
    <div class="notifications-section">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <h3>No notifications found</h3>
                <p>You're all caught up! No notifications match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['status'] === 'unread' ? 'unread' : ''; ?>">
                        <div class="notification-indicator"></div>
                        <div class="notification-content">
                            <div class="notification-header">
                                <h4 class="notification-title"><?= htmlspecialchars($notification['title']); ?></h4>
                                <div class="notification-actions">
                                    <?php if ($notification['status'] === 'unread'): ?>
                                        <a href="<?= base_url('staff/dashboard/notifications/mark-read?id=' . $notification['id']); ?>" class="btn-icon" title="Mark as read">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= base_url('staff/dashboard/notifications/delete?id=' . $notification['id']); ?>" class="btn-icon btn-icon-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this notification?');">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <p class="notification-message"><?= htmlspecialchars($notification['message']); ?></p>
                            <div class="notification-meta">
                                <span class="notification-time">
                                    <?php
                                    $createdAt = strtotime($notification['created_at']);
                                    $now = time();
                                    $diff = $now - $createdAt;
                                    
                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . ' minute' . (floor($diff / 60) !== 1 ? 's' : '') . ' ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hour' . (floor($diff / 3600) !== 1 ? 's' : '') . ' ago';
                                    } elseif ($diff < 604800) {
                                        echo floor($diff / 86400) . ' day' . (floor($diff / 86400) !== 1 ? 's' : '') . ' ago';
                                    } else {
                                        echo date('M j, Y g:i A', $createdAt);
                                    }
                                    ?>
                                </span>
                                <?php if (!empty($notification['role_key'])): ?>
                                    <span class="notification-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $notification['role_key']))); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.notifications-header {
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
    flex-wrap: wrap;
}

.notifications-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.notifications-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.notifications-subtitle strong {
    color: #8b5cf6;
    font-weight: 600;
}

.notifications-filters {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #1e293b;
}

.modern-select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.notifications-section {
    margin-top: 2rem;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
    position: relative;
}

.notification-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.notification-item.unread {
    background: #f8fafc;
    border-left: 4px solid #8b5cf6;
}

.notification-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.notification-item.unread .notification-indicator {
    background: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.notification-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    flex: 1;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #475569;
}

.btn-icon-danger {
    color: #ef4444;
    border-color: #fecaca;
}

.btn-icon-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}

.notification-message {
    margin: 0 0 0.75rem 0;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.5;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.notification-time {
    font-size: 0.875rem;
    color: #64748b;
}

.notification-role {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #e0e7ff;
    color: #6366f1;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
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
    .notifications-header {
        flex-direction: column;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .notification-item {
        padding: 1rem;
    }

    .notification-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .notification-actions {
        align-self: flex-end;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

