<?php
$pageTitle = 'Bills Management | Hotela';
$bills = $bills ?? [];
$summary = $summary ?? ['total_amount' => 0, 'total_count' => 0, 'pending_amount' => 0, 'paid_amount' => 0];
$bySupplier = $bySupplier ?? [];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d'), 'department' => '', 'status' => '', 'supplier_id' => null];
$suppliers = $suppliers ?? [];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

$departments = [
    'operations' => 'Operations',
    'finance' => 'Finance',
    'kitchen' => 'Kitchen',
    'housekeeping' => 'Housekeeping',
    'maintenance' => 'Maintenance',
    'security' => 'Security',
    'front_desk' => 'Front Desk',
    'management' => 'Management',
];

ob_start();
?>
<section class="card">
    <header class="bills-header">
        <div>
            <h2>Bills Management</h2>
            <p class="bills-subtitle">Track and manage supplier bills and invoices for <?= htmlspecialchars($dateRangeLabel); ?></p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #64748b;">
                <a href="<?= base_url('staff/dashboard/expenses'); ?>" style="color: var(--primary); text-decoration: none;">View Expenses →</a>
            </p>
        </div>
        <a href="<?= base_url('staff/dashboard/bills/create'); ?>" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Bill
        </a>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Time Period Quick Filters -->
    <div class="time-period-filters" style="display:flex;gap:0.5rem;margin-bottom:1rem;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;border:1px solid #e5e7eb;">
        <button type="button" class="time-period-btn" data-period="custom">Custom</button>
        <button type="button" class="time-period-btn" data-period="today">Today</button>
        <button type="button" class="time-period-btn" data-period="week">This Week</button>
        <button type="button" class="time-period-btn" data-period="month">This Month</button>
        <button type="button" class="time-period-btn" data-period="year">This Year</button>
        <button type="button" class="time-period-btn" data-period="all">All Time</button>
    </div>

    <form method="get" action="<?= base_url('staff/dashboard/bills'); ?>" class="bills-filters" id="report-filter-form">
        <div class="filter-grid">
            <label>
                <span>Start Date</span>
                <input type="date" name="start" id="date-start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end" id="date-end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
            </label>
            <label>
                <span>Department</span>
                <select name="department" class="modern-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key); ?>" <?= $filters['department'] === $key ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </label>
            <label>
                <span>Supplier</span>
                <select name="supplier_id" class="modern-select">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['id']; ?>" <?= $filters['supplier_id'] == $supplier['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <button type="button" class="btn btn-outline" id="clear-filters">Clear</button>
            </div>
        </div>
    </form>

    <!-- Summary KPIs -->
    <div class="bills-kpis">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Bills</span>
                <span class="kpi-value">KES <?= number_format($summary['total_amount'] ?? 0, 2); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Bill Count</span>
                <span class="kpi-value"><?= number_format($summary['total_count'] ?? 0); ?></span>
            </div>
        </div>
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Pending Bills</span>
                <span class="kpi-value">KES <?= number_format($summary['pending_amount'] ?? 0, 2); ?></span>
            </div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Paid Bills</span>
                <span class="kpi-value">KES <?= number_format($summary['paid_amount'] ?? 0, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- By Supplier Breakdown -->
    <?php if (!empty($bySupplier)): ?>
        <div class="breakdown-card">
            <h3 class="breakdown-title">Bills by Supplier</h3>
            <div class="breakdown-list">
                <?php foreach (array_slice($bySupplier, 0, 10) as $sup): ?>
                    <div class="breakdown-item">
                        <div class="breakdown-info">
                            <a href="<?= base_url('staff/dashboard/suppliers/show?id=' . $sup['supplier_id']); ?>" class="breakdown-label" style="text-decoration: none; color: var(--primary);">
                                <?= htmlspecialchars($sup['supplier_name']); ?>
                            </a>
                            <span class="breakdown-count"><?= number_format($sup['count']); ?> bills</span>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                            <span class="breakdown-amount">KES <?= number_format($sup['total_amount'], 2); ?></span>
                            <span style="font-size: 0.875rem; color: #64748b;">Paid: KES <?= number_format($sup['paid_amount'], 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bills List -->
    <div class="bills-section">
        <h3 class="section-title">Bill Records</h3>
        <?php if (empty($bills)): ?>
            <div class="empty-state">
                <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <h3>No Bills Found</h3>
                <p>No bills match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="bills-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bill Reference</th>
                            <th>Supplier</th>
                            <th>Description</th>
                            <th>Department</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td>
                                    <div class="date-time">
                                        <span class="date"><?= date('M j, Y', strtotime($bill['expense_date'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="reference"><?= htmlspecialchars($bill['bill_reference']); ?></span>
                                </td>
                                <td>
                                    <a href="<?= base_url('staff/dashboard/suppliers/show?id=' . $bill['supplier_id']); ?>" class="supplier-link">
                                        <?= htmlspecialchars($bill['supplier_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="description"><?= htmlspecialchars($bill['description']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($bill['department'])): ?>
                                        <span class="department-badge"><?= htmlspecialchars($departments[$bill['department']] ?? ucfirst($bill['department'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="amount-value">KES <?= number_format((float)$bill['amount'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($bill['status']); ?>">
                                        <?= ucfirst($bill['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('staff/dashboard/bills/show?id=' . $bill['id']); ?>" class="btn-icon" title="View">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <?php if (in_array($bill['status'], ['pending', 'approved'])): ?>
                                            <a href="<?= base_url('staff/dashboard/bills/edit?id=' . $bill['id']); ?>" class="btn-icon" title="Edit">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($bill['status'] === 'pending'): ?>
                                            <a href="<?= base_url('staff/dashboard/bills/approve?id=' . $bill['id']); ?>" class="btn-icon btn-approve" title="Approve" onclick="return confirm('Approve this bill?');">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($bill['status'] === 'approved'): ?>
                                            <a href="<?= base_url('staff/dashboard/bills/mark-paid?id=' . $bill['id']); ?>" class="btn-icon btn-paid" title="Mark as Paid" onclick="return confirm('Mark this bill as paid? This will update the supplier balance.');">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                                </svg>
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
    </div>
</section>

<style>
.bills-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.bills-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.bills-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.bills-filters {
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

.filter-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-grid label span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark);
}

.modern-input,
.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.bills-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.kpi-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.kpi-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.kpi-card.kpi-primary {
    background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
    border-color: var(--primary);
    color: #fff;
}

.kpi-card.kpi-warning {
    border-left: 4px solid #f59e0b;
}

.kpi-card.kpi-success {
    border-left: 4px solid #16a34a;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    background: rgba(138, 106, 63, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}

.kpi-card.kpi-primary .kpi-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.kpi-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.kpi-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
}

.kpi-card.kpi-primary .kpi-label {
    color: rgba(255, 255, 255, 0.9);
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.kpi-card.kpi-primary .kpi-value {
    color: #fff;
}

.breakdown-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.breakdown-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.breakdown-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.breakdown-label {
    font-weight: 600;
    color: var(--dark);
}

.breakdown-count {
    font-size: 0.875rem;
    color: #64748b;
}

.breakdown-amount {
    font-weight: 700;
    color: var(--primary);
}

.bills-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.bills-table-wrapper {
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

.date-time {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date {
    font-weight: 600;
    color: var(--dark);
}

.reference {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary);
}

.description {
    color: var(--dark);
}

.department-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: #e0e7ff;
    color: #6366f1;
}

.supplier-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.supplier-link:hover {
    text-decoration: underline;
}

.amount-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.status-pending {
    background: #fef3c7;
    color: #f59e0b;
}

.status-badge.status-approved {
    background: #dbeafe;
    color: #2563eb;
}

.status-badge.status-paid {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-icon:hover {
    color: var(--primary);
    background: rgba(138, 106, 63, 0.1);
    border-color: var(--primary);
}

.btn-icon.btn-approve:hover {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.1);
    border-color: #2563eb;
}

.btn-icon.btn-paid:hover {
    color: #16a34a;
    background: rgba(22, 163, 74, 0.1);
    border-color: #16a34a;
}

.text-muted {
    color: #94a3b8;
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
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .bills-header {
        flex-direction: column;
        gap: 1rem;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .bills-kpis {
        grid-template-columns: 1fr;
    }

    .bills-table-wrapper {
        overflow-x: scroll;
    }
}
</style>

<script>
let activeTimePeriod = null;

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setTimePeriod(period) {
    activeTimePeriod = period;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let dateFrom = '';
    let dateTo = formatLocalDate(today);
    
    switch(period) {
        case 'custom':
            document.getElementById('date-start')?.focus();
            activeTimePeriod = 'custom';
            document.querySelectorAll('.time-period-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === 'custom');
            });
            return;
        case 'today':
            dateFrom = dateTo;
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(today.getDate() - 7);
            dateFrom = formatLocalDate(weekAgo);
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setMonth(today.getMonth() - 1);
            dateFrom = formatLocalDate(monthAgo);
            break;
        case 'year':
            const yearAgo = new Date(today);
            yearAgo.setFullYear(today.getFullYear() - 1);
            dateFrom = formatLocalDate(yearAgo);
            break;
        case 'all':
            dateFrom = '';
            dateTo = '';
            break;
    }
    
    document.getElementById('date-start').value = dateFrom;
    document.getElementById('date-end').value = dateTo;
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    
    document.getElementById('report-filter-form').submit();
}

function initFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const dateFrom = urlParams.get('start') || '';
    const dateTo = urlParams.get('end') || '';
    
    if (dateFrom && dateTo) {
        const today = formatLocalDate(new Date());
        const weekAgoDate = new Date();
        weekAgoDate.setDate(weekAgoDate.getDate() - 7);
        const weekAgo = formatLocalDate(weekAgoDate);
        const monthAgoDate = new Date();
        monthAgoDate.setMonth(monthAgoDate.getMonth() - 1);
        const monthAgo = formatLocalDate(monthAgoDate);
        const yearAgoDate = new Date();
        yearAgoDate.setFullYear(yearAgoDate.getFullYear() - 1);
        const yearAgo = formatLocalDate(yearAgoDate);
        
        if (dateFrom === today && dateTo === today) {
            activeTimePeriod = 'today';
        } else if (dateFrom === weekAgo && dateTo === today) {
            activeTimePeriod = 'week';
        } else if (dateFrom === monthAgo && dateTo === today) {
            activeTimePeriod = 'month';
        } else if (dateFrom === yearAgo && dateTo === today) {
            activeTimePeriod = 'year';
        } else {
            activeTimePeriod = 'custom';
        }
    } else if (!dateFrom && !dateTo) {
        activeTimePeriod = 'all';
    }
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === activeTimePeriod);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    
    document.querySelectorAll('.time-period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimePeriod(this.dataset.period);
        });
    });
    
    document.getElementById('clear-filters')?.addEventListener('click', function() {
        window.location.href = '<?= base_url('staff/dashboard/bills'); ?>';
    });
    
    document.getElementById('date-start')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
    document.getElementById('date-end')?.addEventListener('change', function() {
        activeTimePeriod = null;
        document.querySelectorAll('.time-period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });
});
</script>

<style>
.time-period-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.time-period-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.time-period-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

