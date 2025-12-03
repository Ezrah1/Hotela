<?php
$pageTitle = 'Staff | Hotela';
ob_start();
?>
<section class="card">
    <header class="staff-header">
        <div>
            <h2>Staff Management</h2>
            <p class="staff-subtitle">View and manage your team members</p>
        </div>
        <a href="<?= base_url('staff/dashboard/staff/create'); ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Staff
        </a>
    </header>

    <div class="staff-filters">
        <form method="get" action="<?= base_url('staff/dashboard/staff'); ?>" class="filter-form">
            <div class="filter-inputs">
                <label>
                    <span>Role</span>
                    <select name="role" class="filter-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['key']); ?>" <?= ($activeRole ?? '') === $role['key'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?= ($activeStatus ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= ($activeStatus ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </label>
                <label>
                    <span>Search</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search ?? ''); ?>" placeholder="Search by name or email..." class="filter-input">
                </label>
                <button class="btn btn-outline" type="submit">Apply Filters</button>
                <?php if ($activeRole || $activeStatus || $search): ?>
                    <a href="<?= base_url('staff/dashboard/staff'); ?>" class="btn btn-ghost">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger" style="padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="alert success" style="padding: 1rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; background: #dcfce7; border: 1px solid #bbf7d0; color: #166534;">
            <?= htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>No staff members found</h3>
            <p>No users match your current filters.</p>
        </div>
    <?php else: ?>
        <div class="staff-table-wrapper">
            <table class="staff-table">
                <thead>
                <tr>
                    <th>Staff Member</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Member Since</th>
                    <?php
                    $userRole = (\App\Support\Auth::user()['role_key'] ?? (\App\Support\Auth::user()['role'] ?? ''));
                    if (in_array($userRole, ['admin'])):
                    ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $isCurrentUser = isset($currentUserId) && (int)$user['id'] === (int)$currentUserId;
                    ?>
                    <tr class="<?= $user['status'] === 'inactive' ? 'row-inactive' : ''; ?> <?= $isCurrentUser ? 'row-current-user' : ''; ?>">
                        <td>
                            <div class="staff-cell">
                                <div class="staff-avatar-small">
                                    <span><?= strtoupper(substr($user['name'], 0, 1)); ?></span>
                                </div>
                                <div class="staff-info-cell">
                                    <div class="staff-name"><?= htmlspecialchars($user['name']); ?></div>
                                    <div class="staff-email"><?= htmlspecialchars($user['email']); ?></div>
                                    <?php if (!empty($user['username'])): ?>
                                        <div class="staff-username" style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">Username: <strong><?= htmlspecialchars($user['username']); ?></strong></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            // Get all roles for this user
                            $userRepository = new \App\Repositories\UserRepository();
                            $userRoles = $userRepository->getUserRoles((int)$user['id']);
                            
                            if (count($userRoles) > 1): 
                                // Multiple roles - show all with primary badge
                                $primaryRole = $userRepository->getPrimaryRole((int)$user['id']);
                            ?>
                                <div class="roles-badge-group" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <?php foreach ($userRoles as $role): ?>
                                        <span class="role-badge <?= $role['role_key'] === $primaryRole ? 'role-badge-primary' : ''; ?>" 
                                              title="<?= $role['role_key'] === $primaryRole ? 'Primary Role' : 'Additional Role'; ?>">
                                            <?= htmlspecialchars($role['role_name'] ?? $role['role_key']); ?>
                                            <?php if ($role['role_key'] === $primaryRole): ?>
                                                <span style="font-size: 0.7em; opacity: 0.8;">(Primary)</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: 
                                // Single role
                                $roleName = $user['role_name'] ?? $user['role_key'] ?? 'Unknown';
                            ?>
                                <span class="role-badge"><?= htmlspecialchars($roleName); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isCurrentUser): ?>
                                <span class="login-status current-login">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Currently logged in
                                </span>
                            <?php elseif ($user['last_login_at']): ?>
                                <span class="login-status">
                                    <?= date('M j, Y g:i A', strtotime($user['last_login_at'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="login-status text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="member-since"><?= date('M j, Y', strtotime($user['created_at'])); ?></span>
                        </td>
                        <?php
                        $currentUser = \App\Support\Auth::user();
                        $userRoles = $currentUser['role_keys'] ?? [];
                        if (empty($userRoles) && isset($currentUser['role_key'])) {
                            $userRoles = [$currentUser['role_key']];
                        }
                        $canEdit = in_array('director', $userRoles, true) || in_array('operation_manager', $userRoles, true) || in_array('admin', $userRoles, true);
                        if ($canEdit):
                        ?>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                <a href="<?= base_url('staff/dashboard/staff/profile?id=' . (int)$user['id']); ?>" class="task-action-link" title="View Profile">
                                    View
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <?php if (in_array('director', $userRoles, true)): ?>
                                <a href="<?= base_url('staff/dashboard/staff/edit?id=' . (int)$user['id']); ?>" class="task-action-link" title="Edit User">
                                    Edit
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                    <form method="post" action="<?= base_url('staff/dashboard/attendance/grant-override'); ?>" style="display: inline;" onsubmit="return confirm('Grant 1-hour temporary login access to <?= htmlspecialchars($user['name']); ?>?');">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                                        <input type="hidden" name="reason" value="Granted by admin from Staff Management">
                                        <input type="hidden" name="redirect" value="<?= base_url('staff/dashboard/staff'); ?>">
                                        <button type="submit" class="task-action-link" style="background: none; border: none; padding: 0; cursor: pointer; color: var(--primary); text-decoration: none; font-size: inherit; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            Grant Login
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 11l3 3L22 4"></path>
                                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.staff-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.staff-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.staff-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.staff-filters {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-select,
.filter-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.filter-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.btn-ghost {
    padding: 0.625rem 1rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-ghost:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(138, 106, 63, 0.05);
}

.staff-table-wrapper {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.staff-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.staff-table thead {
    background: #f8fafc;
}

.staff-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}

.staff-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.95rem;
    color: var(--dark);
}

.staff-table tbody tr:last-child td {
    border-bottom: none;
}

.staff-table tbody tr:hover {
    background: #f8fafc;
}

.staff-table tbody tr.row-inactive {
    opacity: 0.7;
    background: #f8fafc;
}

.staff-table tbody tr.row-current-user {
    background: rgba(138, 106, 63, 0.05);
    border-left: 3px solid var(--primary);
}

.staff-table tbody tr.row-current-user:hover {
    background: rgba(138, 106, 63, 0.08);
}

.staff-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.staff-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    flex-shrink: 0;
}

.staff-info-cell {
    min-width: 0;
}

.staff-name {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.staff-email {
    font-size: 0.875rem;
    color: #64748b;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.25rem;
    color: var(--primary);
    font-weight: 600;
    font-size: 0.875rem;
}

.role-badge-primary {
    background: rgba(138, 106, 63, 0.2);
    border: 1px solid var(--primary);
    font-weight: 700;
}

.roles-badge-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-active {
    background: #dcfce7;
    color: #16a34a;
}

.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

.login-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.login-status.current-login {
    color: var(--primary);
    font-weight: 600;
}

.login-status.current-login svg {
    color: var(--primary);
}

.login-status.text-muted {
    color: #94a3b8;
    font-style: italic;
}

.member-since {
    font-size: 0.875rem;
    color: #64748b;
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

@media (max-width: 768px) {
    .filter-inputs {
        grid-template-columns: 1fr;
    }

    .staff-table-wrapper {
        overflow-x: scroll;
    }

    .staff-table {
        min-width: 800px;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
