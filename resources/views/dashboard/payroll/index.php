<?php
$pageTitle = 'Payroll Management | Hotela';
ob_start();
?>
<section class="card">
    <header class="payroll-header">
        <div>
            <h2>Payroll Management</h2>
            <p class="payroll-subtitle">Generate and manage employee payroll</p>
        </div>
        <form method="post" action="<?= base_url('staff/dashboard/payroll/generate'); ?>" style="display: inline-block;">
            <input type="hidden" name="period" value="<?= htmlspecialchars($period ?? date('Y-m')); ?>">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Generate Payroll
            </button>
        </form>
    </header>

    <form method="get" action="<?= base_url('staff/dashboard/payroll'); ?>" class="payroll-filters">
        <div class="filter-grid">
            <label>
                <span>Period</span>
                <input type="month" name="period" value="<?= htmlspecialchars($period ?? date('Y-m')); ?>" class="modern-input">
            </label>
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processed" <?= ($status ?? '') === 'processed' ? 'selected' : ''; ?>>Processed</option>
                    <option value="paid" <?= ($status ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <?php if (empty($payrolls)): ?>
        <div class="empty-state">
            <h3>No payroll records found</h3>
            <p>No payroll records match your current filters. Generate payroll for the selected period.</p>
        </div>
    <?php else: ?>
        <div class="payroll-table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $payroll): ?>
                        <tr>
                            <td>
                                <div class="employee-name"><?= htmlspecialchars($payroll['employee_name']); ?></div>
                                <div class="employee-email"><?= htmlspecialchars($payroll['employee_email']); ?></div>
                            </td>
                            <td>
                                <?= date('M Y', strtotime($payroll['pay_period_end'])); ?>
                            </td>
                            <td>KES <?= number_format((float)$payroll['basic_salary'], 2); ?></td>
                            <td>KES <?= number_format((float)$payroll['allowances'], 2); ?></td>
                            <td>KES <?= number_format((float)$payroll['deductions'], 2); ?></td>
                            <td><strong>KES <?= number_format((float)$payroll['net_salary'], 2); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($payroll['status']); ?>">
                                    <?= htmlspecialchars(ucfirst($payroll['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('staff/dashboard/payroll/edit?id=' . (int)$payroll['id']); ?>" class="btn btn-outline btn-small">
                                    Edit
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
.payroll-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.payroll-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.payroll-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.payroll-filters {
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

.payroll-table-wrapper {
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

