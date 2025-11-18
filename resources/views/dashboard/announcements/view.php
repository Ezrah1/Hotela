<?php
$pageTitle = 'View Announcement | Hotela';
$announcement = $announcement ?? null;

if (!$announcement) {
    header('Location: ' . base_url('dashboard/announcements?error=' . urlencode('Announcement not found')));
    exit;
}

$user = \App\Support\Auth::user();
$canEdit = in_array($user['role'] ?? '', ['admin', 'finance_manager', 'operation_manager']);

ob_start();
?>
<section class="card">
    <header class="announcement-view-header">
        <a href="<?= base_url('dashboard/announcements'); ?>" class="btn btn-outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back
        </a>
        <?php if ($canEdit): ?>
            <div class="header-actions">
                <a href="<?= base_url('dashboard/announcements/edit?id=' . $announcement['id']); ?>" class="btn btn-outline">
                    Edit
                </a>
                <a href="<?= base_url('dashboard/announcements/delete?id=' . $announcement['id']); ?>" class="btn btn-outline btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?');">
                    Delete
                </a>
            </div>
        <?php endif; ?>
    </header>

    <div class="announcement-view">
        <div class="announcement-view-header-info">
            <h2><?= htmlspecialchars($announcement['title']); ?></h2>
            <span class="badge badge-priority badge-<?= $announcement['priority']; ?>">
                <?= ucfirst($announcement['priority']); ?>
            </span>
        </div>

        <div class="announcement-view-meta">
            <div class="meta-item">
                <strong>Author:</strong>
                <span><?= htmlspecialchars($announcement['author_name'] ?? 'Unknown'); ?></span>
            </div>
            <div class="meta-item">
                <strong>Target:</strong>
                <span>
                    <?php
                    if ($announcement['target_audience'] === 'all') {
                        echo 'All Users';
                    } elseif ($announcement['target_audience'] === 'roles') {
                        echo 'Roles: ' . implode(', ', array_map(function($r) {
                            return ucfirst(str_replace('_', ' ', $r));
                        }, $announcement['target_roles'] ?? []));
                    } else {
                        echo 'Specific Users';
                    }
                    ?>
                </span>
            </div>
            <div class="meta-item">
                <strong>Published:</strong>
                <span><?= $announcement['publish_at'] ? date('F j, Y g:i A', strtotime($announcement['publish_at'])) : date('F j, Y g:i A', strtotime($announcement['created_at'])); ?></span>
            </div>
            <?php if ($announcement['expires_at']): ?>
                <div class="meta-item">
                    <strong>Expires:</strong>
                    <span><?= date('F j, Y g:i A', strtotime($announcement['expires_at'])); ?></span>
                </div>
            <?php endif; ?>
            <div class="meta-item">
                <strong>Status:</strong>
                <span class="status-badge status-<?= $announcement['status']; ?>">
                    <?= ucfirst($announcement['status']); ?>
                </span>
            </div>
        </div>

        <div class="announcement-view-content">
            <div class="announcement-body">
                <?= nl2br(htmlspecialchars($announcement['content'])); ?>
            </div>
        </div>
    </div>
</section>

<style>
.announcement-view-header {
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

.announcement-view-header-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.announcement-view-header-info h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.announcement-view-meta {
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

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-draft {
    background: #f3f4f6;
    color: #374151;
}

.status-published {
    background: #d1fae5;
    color: #065f46;
}

.status-archived {
    background: #e5e7eb;
    color: #4b5563;
}

.announcement-view-content {
    padding: 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
}

.announcement-body {
    color: #1e293b;
    line-height: 1.7;
    font-size: 1rem;
    white-space: pre-wrap;
}

.badge-priority {
    padding: 0.375rem 0.875rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
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
    .announcement-view-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .announcement-view-meta {
        grid-template-columns: 1fr;
    }

    .announcement-view-content {
        padding: 1.5rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

