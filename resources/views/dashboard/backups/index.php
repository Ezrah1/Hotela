<?php
$pageTitle = 'Backup Management | Hotela';
ob_start();
?>

<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Backup Management</h2>
            <p>Create, download, and manage system backups</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-outline" onclick="createBackup('full')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Full Backup
            </button>
            <button type="button" class="btn btn-primary" onclick="createBackup('database')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
                Database Backup
            </button>
        </div>
    </header>

    <div class="card">
        <div class="card-header">
            <h3>Available Backups</h3>
            <div class="card-header-actions">
                <span class="text-muted">Total Size: <?= number_format($totalSize / 1024 / 1024, 2); ?> MB</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.3;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p>No backups found</p>
                    <p class="text-muted">Create your first backup to get started</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Backup Name</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($backup['name']); ?></strong>
                                        <?php if (isset($backup['Database'])): ?>
                                            <br><small class="text-muted">Database: <?= htmlspecialchars($backup['Database']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= ($backup['type'] ?? $backup['Backup Type'] ?? 'Full') === 'Full' ? 'primary' : 'info'; ?>">
                                            <?= htmlspecialchars($backup['type'] ?? $backup['Backup Type'] ?? 'Full'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('Y-m-d H:i:s', $backup['created']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $size = $backup['size'] ?? 0;
                                        if ($size >= 1024 * 1024) {
                                            echo number_format($size / 1024 / 1024, 2) . ' MB';
                                        } elseif ($size >= 1024) {
                                            echo number_format($size / 1024, 2) . ' KB';
                                        } else {
                                            echo $size . ' B';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= base_url('staff/dashboard/backups/download?name=' . urlencode($backup['name'])); ?>" 
                                               class="btn btn-sm btn-outline" 
                                               title="Download">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="deleteBackup('<?= htmlspecialchars(addslashes($backup['name'])); ?>')"
                                                    title="Delete">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Backup Information</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div>
                    <strong>Backup Location:</strong>
                    <p class="text-muted"><?= htmlspecialchars($backupDir); ?></p>
                </div>
                <div>
                    <strong>Backup Types:</strong>
                    <ul class="text-muted">
                        <li><strong>Full Backup:</strong> Database + Files</li>
                        <li><strong>Database Only:</strong> SQL dump only</li>
                        <li><strong>Files Only:</strong> Application files only</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>Note:</strong> Backups are stored locally. For production environments, consider:
                <ul style="margin-top: 0.5rem; margin-bottom: 0;">
                    <li>Setting up automated daily backups</li>
                    <li>Storing backups in a secure off-site location</li>
                    <li>Testing restore procedures regularly</li>
                    <li>Encrypting sensitive backup data</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<script>
function createBackup(type) {
    if (!confirm(`Create a ${type === 'full' ? 'full' : 'database'} backup? This may take a few moments.`)) {
        return;
    }
    
    fetch('<?= base_url('staff/dashboard/backups/create'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'type=' + encodeURIComponent(type)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alert('Error: ' + (data.message || 'Failed to create backup'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the backup');
    });
}

function deleteBackup(name) {
    if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        return;
    }
    
    fetch('<?= base_url('staff/dashboard/backups/delete'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'name=' + encodeURIComponent(name)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete backup'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the backup');
    });
}
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

