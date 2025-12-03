<?php
$pageTitle = 'Human Resources | Hotela';
ob_start();
?>
<section class="card">
    <header class="hr-header">
        <div>
            <h2>Human Resources</h2>
            <p class="hr-subtitle">Manage employee records and information</p>
        </div>
        <a href="<?= base_url('staff/dashboard/staff/create'); ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Staff
        </a>
    </header>

    <form method="get" action="<?= base_url('staff/dashboard/hr'); ?>" class="hr-filters">
        <div class="filter-grid">
            <label>
                <span>Search</span>
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? ''); ?>" placeholder="Search employees..." class="modern-input">
            </label>
            <label>
                <span>Role</span>
                <select name="role" class="modern-select">
                    <option value="">All Roles</option>
                    <?php foreach ($roles ?? [] as $role): ?>
                        <option value="<?= htmlspecialchars($role['key']); ?>" <?= ($roleFilter ?? '') === $role['key'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Status</option>
                    <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/hr'); ?>">Clear</a>
            </div>
        </div>
    </form>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3>No employees found</h3>
            <p>No employees match your current filters.</p>
        </div>
    <?php else: ?>
        <div class="hr-table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $employee): ?>
                        <tr>
                            <td>
                                <div class="employee-cell">
                                    <div class="employee-avatar-small">
                                        <span><?= strtoupper(substr($employee['name'], 0, 1)); ?></span>
                                    </div>
                                    <div>
                                        <div class="employee-name"><?= htmlspecialchars($employee['name']); ?></div>
                                        <div class="employee-email"><?= htmlspecialchars($employee['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                // Get all roles for this employee
                                $userRepository = new \App\Repositories\UserRepository();
                                $userRoles = $userRepository->getUserRoles((int)$employee['id']);
                                
                                if (count($userRoles) > 1): 
                                    // Multiple roles - show all with primary badge
                                    $primaryRole = $userRepository->getPrimaryRole((int)$employee['id']);
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
                                    $roleName = $employee['role_name'] ?? $employee['role_key'] ?? 'Unknown';
                                ?>
                                    <span class="role-badge"><?= htmlspecialchars($roleName); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['status'] === 'active'): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                    <a href="<?= base_url('staff/dashboard/hr/employee?id=' . (int)$employee['id']); ?>" class="btn btn-outline btn-small" title="View Employee Records">
                                        View Records
                                    </a>
                                    <?php
                                    $currentUser = \App\Support\Auth::user();
                                    $userRoles = $currentUser['role_keys'] ?? [];
                                    if (empty($userRoles) && isset($currentUser['role_key'])) {
                                        $userRoles = [$currentUser['role_key']];
                                    }
                                    if (in_array('director', $userRoles, true)): 
                                    ?>
                                        <a href="<?= base_url('staff/dashboard/staff/edit?id=' . (int)$employee['id']); ?>" class="btn btn-outline btn-small" title="Edit Employee">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.hr-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.hr-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.hr-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.hr-filters {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.modern-input,
.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.hr-table-wrapper {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: var(--dark);
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.employee-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employee-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.employee-name {
    font-weight: 600;
    color: var(--dark);
}

.employee-email {
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
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

