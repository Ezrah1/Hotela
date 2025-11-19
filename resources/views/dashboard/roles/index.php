<?php
$pageTitle = 'Roles Management | Hotela';
ob_start();
?>
<section class="card">
    <header class="roles-header">
        <div>
            <h2>Roles Management</h2>
            <p class="roles-subtitle">View and manage system roles and permissions</p>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($roles)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>No roles found</h3>
            <p>No roles are configured in the system.</p>
        </div>
    <?php else: ?>
        <div class="roles-grid">
            <?php foreach ($roles as $roleKey => $role): ?>
                <?php
                $userCount = $roleCounts[$roleKey] ?? 0;
                $hasAllPermissions = in_array('*', $role['permissions'] ?? [], true);
                ?>
                <div class="role-card">
                    <div class="role-card-header">
                        <div class="role-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="role-info">
                            <h3 class="role-name"><?= htmlspecialchars($role['label'] ?? $roleKey); ?></h3>
                            <p class="role-key"><?= htmlspecialchars($roleKey); ?></p>
                        </div>
                        <div class="role-badge">
                            <span class="badge-count"><?= $userCount; ?></span>
                            <span class="badge-label"><?= $userCount === 1 ? 'User' : 'Users'; ?></span>
                        </div>
                    </div>
                    <div class="role-card-body">
                        <div class="role-permissions">
                            <?php if ($hasAllPermissions): ?>
                                <div class="permission-tag permission-all">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    All Permissions
                                </div>
                            <?php else: ?>
                                <?php
                                $permissions = $role['permissions'] ?? [];
                                $displayCount = count($permissions);
                                $showPermissions = array_slice($permissions, 0, 3);
                                ?>
                                <?php foreach ($showPermissions as $permission): ?>
                                    <span class="permission-tag"><?= htmlspecialchars($permission); ?></span>
                                <?php endforeach; ?>
                                <?php if ($displayCount > 3): ?>
                                    <span class="permission-tag permission-more">+<?= $displayCount - 3; ?> more</span>
                                <?php endif; ?>
                                <?php if ($displayCount === 0): ?>
                                    <span class="permission-tag permission-none">No permissions</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($role['dashboard_view'])): ?>
                            <div class="role-dashboard">
                                <span class="dashboard-label">Dashboard:</span>
                                <span class="dashboard-value"><?= htmlspecialchars(str_replace('dashboard/roles/', '', $role['dashboard_view'])); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="role-actions">
                            <a href="<?= base_url('staff/dashboard/roles/edit?role=' . urlencode($roleKey)); ?>" class="btn btn-outline btn-small">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit Permissions
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.roles-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.roles-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.roles-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.role-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.role-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.role-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.role-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}

.role-info {
    flex: 1;
    min-width: 0;
}

.role-name {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.role-key {
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
    font-family: 'Courier New', monospace;
}

.role-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.badge-count {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
}

.badge-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.25rem;
}

.role-card-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.role-permissions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.permission-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--primary);
}

.permission-tag.permission-all {
    background: #dcfce7;
    color: #16a34a;
}

.permission-tag.permission-more {
    background: #f1f5f9;
    color: #64748b;
    font-style: italic;
}

.permission-tag.permission-none {
    background: #fee2e2;
    color: #dc2626;
}

.role-dashboard {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.dashboard-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
}

.dashboard-value {
    font-size: 0.875rem;
    color: var(--dark);
    font-family: 'Courier New', monospace;
}

.role-actions {
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
    margin-top: 1rem;
}

.role-actions .btn {
    width: 100%;
    justify-content: center;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: #cbd5e1;
    margin-bottom: 1.5rem;
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
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.danger {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert.success {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    color: #166534;
}

@media (max-width: 768px) {
    .roles-grid {
        grid-template-columns: 1fr;
    }

    .role-card-header {
        flex-wrap: wrap;
    }

    .role-badge {
        width: 100%;
        margin-top: 0.5rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

