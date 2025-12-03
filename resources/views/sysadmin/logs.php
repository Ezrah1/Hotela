<?php
$pageTitle = 'System Audit Logs';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>System Audit Logs</h2>
        <p class="page-subtitle">All system administrator actions are logged here</p>
    </div>
    <div>
        <span class="text-muted">Total: <?= number_format($totalLogs); ?> logs</span>
    </div>
</div>

<div class="sysadmin-card">
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <p>No logs found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="sysadmin-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?= htmlspecialchars($log['username']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <?= htmlspecialchars($log['entity_type']); ?>
                                        <?php if ($log['entity_id']): ?>
                                            #<?= $log['entity_id']; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($log['details']): ?>
                                        <?php
                                        $details = json_decode($log['details'], true);
                                        if ($details):
                                        ?>
                                            <details>
                                                <summary style="cursor: pointer; color: #667eea;">View</summary>
                                                <pre style="margin-top: 0.5rem; padding: 0.5rem; background: #f8fafc; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto;"><?= htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)); ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1; ?>" class="btn btn-outline btn-sm">Previous</a>
                    <?php endif; ?>
                    
                    <span style="display: flex; align-items: center; padding: 0 1rem;">
                        Page <?= $currentPage; ?> of <?= $totalPages; ?>
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1; ?>" class="btn btn-outline btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.badge-info {
    background: #eff6ff;
    color: #2563eb;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

