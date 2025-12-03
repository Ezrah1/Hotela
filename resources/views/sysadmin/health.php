<?php
$pageTitle = 'System Health';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>System Health</h2>
        <p class="page-subtitle">Monitor system resources and performance</p>
    </div>
</div>

<div class="sysadmin-health-grid">
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Database</h3>
        </div>
        <div class="card-body">
            <div class="health-status-large status-<?= $health['database']['status']; ?>">
                <div class="health-status-icon">
                    <?php if ($health['database']['status'] === 'healthy'): ?>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="health-status-text">
                    <div class="health-status-title"><?= htmlspecialchars($health['database']['message']); ?></div>
                    <div class="health-status-subtitle">Connection Status</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Disk Usage</h3>
        </div>
        <div class="card-body">
            <div class="health-status-large status-<?= $health['disk']['status']; ?>">
                <div class="health-status-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="6" y1="3" x2="6" y2="21"></line>
                        <line x1="18" y1="3" x2="18" y2="21"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </div>
                <div class="health-status-text">
                    <div class="health-status-title"><?= number_format($health['disk']['usage_percent'], 1); ?>% Used</div>
                    <div class="health-status-subtitle">
                        <?= number_format($health['disk']['free'] / 1024 / 1024 / 1024, 2); ?> GB free of 
                        <?= number_format($health['disk']['total'] / 1024 / 1024 / 1024, 2); ?> GB
                    </div>
                </div>
            </div>
            <div class="progress-bar" style="margin-top: 1rem;">
                <div class="progress-fill" style="width: <?= $health['disk']['usage_percent']; ?>%; background: <?= $health['disk']['usage_percent'] > 90 ? '#dc2626' : ($health['disk']['usage_percent'] > 75 ? '#f59e0b' : '#10b981'); ?>;"></div>
            </div>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>PHP Version</h3>
        </div>
        <div class="card-body">
            <div class="health-status-large status-<?= $health['php']['status']; ?>">
                <div class="health-status-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <div class="health-status-text">
                    <div class="health-status-title"><?= htmlspecialchars($health['php']['version']); ?></div>
                    <div class="health-status-subtitle">Runtime Version</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Memory Usage</h3>
        </div>
        <div class="card-body">
            <div class="health-item">
                <div class="health-label">Current Usage</div>
                <div class="health-value"><?= number_format($health['memory']['usage'] / 1024 / 1024, 2); ?> MB</div>
            </div>
            <div class="health-item">
                <div class="health-label">Memory Limit</div>
                <div class="health-value"><?= htmlspecialchars($health['memory']['limit']); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
.sysadmin-health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.health-status-large {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
}

.health-status-icon {
    flex-shrink: 0;
}

.health-status-text {
    flex: 1;
}

.health-status-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.health-status-subtitle {
    font-size: 0.875rem;
    opacity: 0.7;
}

.progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 0.375rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 0.375rem;
    transition: width 0.3s;
}

.health-value {
    font-weight: 600;
    color: #1e293b;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

