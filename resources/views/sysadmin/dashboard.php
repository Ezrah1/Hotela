<?php
$pageTitle = 'System Control Center';
ob_start();
?>

<div class="sysadmin-stats-grid">
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #667eea20; color: #667eea;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= htmlspecialchars($stats['tenants'] ?? 0); ?></div>
            <div class="stat-label">Active Tenants</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #10b98120; color: #10b981;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= htmlspecialchars($stats['active_licenses'] ?? 0); ?></div>
            <div class="stat-label">Active Licenses</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #f59e0b20; color: #f59e0b;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= htmlspecialchars($stats['uptime'] ?? 'N/A'); ?></div>
            <div class="stat-label">System Uptime</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #ef444420; color: #ef4444;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= htmlspecialchars($stats['pending_updates'] ?? 0); ?></div>
            <div class="stat-label">Pending Updates</div>
        </div>
    </div>
</div>

<div class="sysadmin-content-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Recent System Actions</h3>
            <a href="<?= base_url('sysadmin/logs'); ?>" class="btn-link">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentActions)): ?>
                <p class="text-muted">No recent actions</p>
            <?php else: ?>
                <div class="action-list">
                    <?php foreach ($recentActions as $action): ?>
                        <div class="action-item">
                            <div class="action-icon">
                                <?php
                                $iconColor = match($action['action']) {
                                    'login' => '#10b981',
                                    'logout' => '#64748b',
                                    'create', 'update', 'delete' => '#667eea',
                                    default => '#f59e0b'
                                };
                                ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?= $iconColor; ?>" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                </svg>
                            </div>
                            <div class="action-details">
                                <div class="action-title"><?= htmlspecialchars($action['action']); ?></div>
                                <div class="action-meta">
                                    <span><?= htmlspecialchars($action['username']); ?></span>
                                    <span>•</span>
                                    <span><?= date('M j, Y H:i', strtotime($action['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>System Health</h3>
        </div>
        <div class="card-body">
            <div class="health-item">
                <div class="health-label">Database</div>
                <div class="health-status status-<?= $health['database']['status']; ?>">
                    <?= htmlspecialchars($health['database']['message']); ?>
                </div>
            </div>
            <div class="health-item">
                <div class="health-label">Disk Usage</div>
                <div class="health-status status-<?= $health['disk']['status']; ?>">
                    <?= number_format($health['disk']['usage_percent'], 1); ?>%
                </div>
            </div>
            <div class="health-item">
                <div class="health-label">PHP Version</div>
                <div class="health-status status-<?= $health['php']['status']; ?>">
                    <?= htmlspecialchars($health['php']['version']); ?>
                </div>
            </div>
            <a href="<?= base_url('sysadmin/health'); ?>" class="btn btn-outline btn-sm" style="margin-top: 1rem; width: 100%;">View Full Health Report</a>
        </div>
    </div>
</div>

<style>
.sysadmin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.sysadmin-stat-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.sysadmin-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #64748b;
}

.sysadmin-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.btn-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.btn-link:hover {
    text-decoration: underline;
}

.card-body {
    padding: 1.5rem;
}

.action-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: background 0.2s;
}

.action-item:hover {
    background: #f8fafc;
}

.action-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.action-details {
    flex: 1;
}

.action-title {
    font-weight: 500;
    color: #1e293b;
    text-transform: capitalize;
    margin-bottom: 0.25rem;
}

.action-meta {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.health-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.health-item:last-child {
    border-bottom: none;
}

.health-label {
    font-weight: 500;
    color: #475569;
}

.health-status {
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.status-healthy {
    background: #f0fdf4;
    color: #166534;
}

.status-warning {
    background: #fffbeb;
    color: #d97706;
}

.status-error {
    background: #fef2f2;
    color: #dc2626;
}

@media (max-width: 1024px) {
    .sysadmin-content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>
