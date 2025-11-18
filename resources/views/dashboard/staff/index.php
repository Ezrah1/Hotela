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
    </header>

    <div class="staff-filters">
        <form method="get" action="<?= base_url('dashboard/staff'); ?>" class="filter-form">
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
                    <a href="<?= base_url('dashboard/staff'); ?>" class="btn btn-ghost">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

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
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge"><?= htmlspecialchars($user['role_name'] ?? $user['role_key']); ?></span>
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
                        $userRole = (\App\Support\Auth::user()['role_key'] ?? (\App\Support\Auth::user()['role'] ?? ''));
                        if (in_array($userRole, ['admin'])):
                        ?>
                            <td>
                                <a href="<?= base_url('dashboard/staff/edit?id=' . (int)$user['id']); ?>" class="task-action-link">
                                    Edit
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
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
