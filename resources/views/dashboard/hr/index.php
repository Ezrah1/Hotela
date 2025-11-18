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
    </header>

    <form method="get" action="<?= base_url('dashboard/hr'); ?>" class="hr-filters">
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
                <a class="btn btn-outline" href="<?= base_url('dashboard/hr'); ?>">Clear</a>
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
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="employee-cell">
                                    <div class="employee-avatar-small">
                                        <span><?= strtoupper(substr($user['name'], 0, 1)); ?></span>
                                    </div>
                                    <div>
                                        <div class="employee-name"><?= htmlspecialchars($user['name']); ?></div>
                                        <div class="employee-email"><?= htmlspecialchars($user['email']); ?></div>
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
                                <a href="<?= base_url('dashboard/hr/employee?id=' . (int)$user['id']); ?>" class="btn btn-outline btn-small">
                                    View Records
                                </a>
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
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

