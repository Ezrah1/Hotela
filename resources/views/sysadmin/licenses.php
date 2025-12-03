<?php
$pageTitle = 'License Management';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>License Management</h2>
        <p class="page-subtitle">View and manage license activations across all installations</p>
    </div>
    <div>
        <a href="<?= base_url('sysadmin/tenants'); ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                <path d="M12 5v14M5 12h14"></path>
            </svg>
            Generate License for Tenant
        </a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border-radius: 0.5rem; border: 1px solid #bbf7d0;">
        <?php if ($_GET['success'] === 'license_generated'): ?>
            License generated and sent successfully!
        <?php elseif ($_GET['success'] === 'license_sent'): ?>
            License email sent successfully!
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="sysadmin-card">
    <div class="card-body">
        <?php if (empty($licenses)): ?>
            <div class="empty-state">
                <p>No license activations found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="sysadmin-table">
                    <thead>
                        <tr>
                            <th>Installation ID</th>
                            <th>Director</th>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Activated</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $license): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 0.75rem;"><?= htmlspecialchars(substr($license['installation_id'], 0, 20)); ?>...</code>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($license['director_name'] ?? 'N/A'); ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($license['director_email'] ?? ''); ?></small>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($license['tenant_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?= $license['status'] === 'active' ? 'success' : ($license['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                                        <?= ucfirst($license['status']); ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($license['activated_at'])); ?></td>
                                <td>
                                    <?= $license['expires_at'] ? date('M j, Y', strtotime($license['expires_at'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('sysadmin/licenses/view?id=' . $license['id']); ?>" class="btn btn-sm btn-outline">View</a>
                                        <form method="POST" action="<?= base_url('sysadmin/licenses/send'); ?>" style="display: inline;">
                                            <input type="hidden" name="license_id" value="<?= $license['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Resend license email to director?')">Resend Email</button>
                                        </form>
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

<style>
.text-muted {
    color: #64748b;
    font-size: 0.875rem;
}

.badge-danger {
    background: #fef2f2;
    color: #dc2626;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

