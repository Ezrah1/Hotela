<?php
$pageTitle = 'Tenant Details: ' . htmlspecialchars($tenant['name']);
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <a href="<?= base_url('sysadmin/tenants'); ?>" class="btn-link" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Tenants
        </a>
        <h2><?= htmlspecialchars($tenant['name']); ?></h2>
        <p class="page-subtitle">View tenant details, users, and activity</p>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border-radius: 0.5rem; border: 1px solid #bbf7d0;">
        <?php
        $messages = [
            'package_assigned' => 'License package assigned successfully! License has been generated and activated.',
        ];
        echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully.');
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 2rem; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border-radius: 0.5rem; border: 1px solid #fecaca;">
        <?php
        $errors = [
            'no_director' => 'No director user found. Please create a director user for this tenant first.',
            'not_found' => 'Tenant or package not found.',
            'missing_data' => 'Missing required information.',
        ];
        echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<div class="sysadmin-stats-grid" style="margin-bottom: 2rem;">
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
            <div class="stat-value"><?= number_format($stats['user_count']); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #10b98120; color: #10b981;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['booking_count_30d']); ?></div>
            <div class="stat-label">Bookings (30 days)</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #f59e0b20; color: #f59e0b;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= ucfirst($stats['license_status']); ?></div>
            <div class="stat-label">License Status</div>
        </div>
    </div>
</div>

<div class="sysadmin-content-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Tenant Information</h3>
        </div>
        <div class="card-body">
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?= htmlspecialchars($tenant['name']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Domain</div>
                <div class="info-value"><?= htmlspecialchars($tenant['domain']); ?></div>
            </div>
            <?php if (!empty($tenant['slug'])): ?>
            <div class="info-item">
                <div class="info-label">Slug</div>
                <div class="info-value"><?= htmlspecialchars($tenant['slug']); ?></div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="badge badge-<?= $tenant['status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?= ucfirst($tenant['status']); ?>
                    </span>
                </div>
            </div>
            <?php if (!empty($tenant['contact_email'])): ?>
            <div class="info-item">
                <div class="info-label">Contact Email</div>
                <div class="info-value"><?= htmlspecialchars($tenant['contact_email']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($tenant['contact_phone'])): ?>
            <div class="info-item">
                <div class="info-label">Contact Phone</div>
                <div class="info-value"><?= htmlspecialchars($tenant['contact_phone']); ?></div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="info-label">Created</div>
                <div class="info-value"><?= date('M j, Y H:i', strtotime($tenant['created_at'])); ?></div>
            </div>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>License Information</h3>
        </div>
        <div class="card-body">
            <?php if ($stats['license_status'] === 'active'): ?>
                <div class="license-info">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>
                    <?php if ($stats['license_expires']): ?>
                    <div class="info-item">
                        <div class="info-label">Expires</div>
                        <div class="info-value"><?= date('M j, Y', strtotime($stats['license_expires'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap;">
                        <a href="<?= base_url('sysadmin/packages/assign?tenant_id=' . $tenant['id']); ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                            Assign Package
                        </a>
                        <form method="POST" action="<?= base_url('sysadmin/licenses/generate'); ?>" style="display: inline;">
                            <input type="hidden" name="tenant_id" value="<?= $tenant['id']; ?>">
                            <button type="submit" class="btn btn-outline" onclick="return confirm('Generate a new license for this tenant? This will deactivate the current license.')">
                                Generate New License
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="license-info">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge badge-warning">Inactive</span>
                        </div>
                    </div>
                    <p class="text-muted" style="margin: 1rem 0;">No active license for this tenant.</p>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a href="<?= base_url('sysadmin/packages/assign?tenant_id=' . $tenant['id']); ?>" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                            Assign License Package
                        </a>
                        <form method="POST" action="<?= base_url('sysadmin/licenses/generate'); ?>" style="display: inline;">
                            <input type="hidden" name="tenant_id" value="<?= $tenant['id']; ?>">
                            <button type="submit" class="btn btn-outline">
                                Generate Free License
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="sysadmin-card">
        <div class="card-header">
            <h3>Users (<?= count($users); ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <p class="text-muted">No users found</p>
            <?php else: ?>
                <div class="user-list">
                    <?php foreach (array_slice($users, 0, 5) as $user): ?>
                        <div class="user-item">
                            <div>
                                <strong><?= htmlspecialchars($user['name']); ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($user['email']); ?></small>
                            </div>
                            <span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', $user['role_key'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($users) > 5): ?>
                        <p class="text-muted" style="margin-top: 1rem; text-align: center;">
                            +<?= count($users) - 5; ?> more users
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Recent Bookings</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentBookings)): ?>
            <p class="text-muted">No recent bookings</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="sysadmin-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($booking['reference']); ?></code></td>
                                <td><?= htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?= date('M j, Y', strtotime($booking['check_in'])); ?></td>
                                <td><?= date('M j, Y', strtotime($booking['check_out'])); ?></td>
                                <td>KES <?= number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                        <?= ucfirst($booking['status']); ?>
                                    </span>
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
.sysadmin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.sysadmin-stat-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

.info-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    color: #1e293b;
}

.user-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.user-item strong {
    display: block;
    margin-bottom: 0.25rem;
}

.badge-info {
    background: #eff6ff;
    color: #2563eb;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9375rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}

.btn svg {
    width: 16px;
    height: 16px;
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

