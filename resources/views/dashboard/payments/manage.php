<?php
$pageTitle = 'Manage Payments | Hotela';
$payments = $payments ?? [];
$summary = $summary ?? ['total_amount' => 0, 'total_count' => 0];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d'), 'payment_type' => '', 'payment_method' => '', 'status' => ''];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));

ob_start();
?>
<section class="card">
    <header class="payments-header">
        <div>
            <h2>Manage Payments</h2>
            <p class="payments-subtitle">Recorded payments for expenses, bills, and suppliers (<?= htmlspecialchars($dateRangeLabel); ?>)</p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url('dashboard/payments/record'); ?>" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Record Payment
            </a>
        </div>
    </header>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <form method="get" action="<?= base_url('dashboard/payments/manage'); ?>" class="payments-filters">
        <div class="filter-grid">
            <label>
                <span>Start Date</span>
                <input type="date" name="start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
            </label>
            <label>
                <span>Payment Type</span>
                <select name="payment_type" class="modern-select">
                    <option value="">All Types</option>
                    <option value="expense" <?= $filters['payment_type'] === 'expense' ? 'selected' : ''; ?>>Expense</option>
                    <option value="bill" <?= $filters['payment_type'] === 'bill' ? 'selected' : ''; ?>>Bill</option>
                    <option value="supplier" <?= $filters['payment_type'] === 'supplier' ? 'selected' : ''; ?>>Supplier</option>
                    <option value="other" <?= $filters['payment_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </label>
            <label>
                <span>Payment Method</span>
                <select name="payment_method" class="modern-select">
                    <option value="">All Methods</option>
                    <option value="cash" <?= $filters['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="bank_transfer" <?= $filters['payment_method'] === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="cheque" <?= $filters['payment_method'] === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                    <option value="mpesa" <?= $filters['payment_method'] === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                    <option value="card" <?= $filters['payment_method'] === 'card' ? 'selected' : ''; ?>>Card</option>
                    <option value="other" <?= $filters['payment_method'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status" class="modern-select">
                    <option value="">All Statuses</option>
                    <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline" href="<?= base_url('dashboard/payments/manage?start=' . urlencode(date('Y-m-01')) . '&end=' . urlencode(date('Y-m-d'))); ?>">This Month</a>
                <a class="btn btn-outline" href="<?= base_url('dashboard/payments/manage?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 Days</a>
            </div>
        </div>
    </form>

    <!-- Summary KPIs -->
    <div class="payments-kpis">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Payments</span>
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
                <span class="kpi-label">Transaction Count</span>
                <span class="kpi-value"><?= number_format($summary['total_count'] ?? 0); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                    <line x1="2" y1="8" x2="22" y2="8"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Expense Payments</span>
                <span class="kpi-value">KES <?= number_format($summary['expense_payments'] ?? 0, 2); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Bill Payments</span>
                <span class="kpi-value">KES <?= number_format($summary['bill_payments'] ?? 0, 2); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Supplier Payments</span>
                <span class="kpi-value">KES <?= number_format($summary['supplier_payments'] ?? 0, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="payments-table-section">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <h3>No payments found</h3>
                <p>No payments match your current filters. Try adjusting your search criteria.</p>
                <a href="<?= base_url('dashboard/payments/record'); ?>" class="btn btn-primary">Record Payment</a>
            </div>
        <?php else: ?>
            <div class="payments-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <div class="date-time">
                                        <span class="date"><?= date('M j, Y', strtotime($payment['payment_date'])); ?></span>
                                        <span class="time"><?= date('g:i A', strtotime($payment['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="reference"><?= htmlspecialchars($payment['reference']); ?></span>
                                </td>
                                <td>
                                    <span class="payment-type type-<?= strtolower($payment['payment_type']); ?>">
                                        <?= ucfirst($payment['payment_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['expense_reference']): ?>
                                        <span>Expense: <?= htmlspecialchars($payment['expense_reference']); ?></span>
                                    <?php elseif ($payment['bill_reference']): ?>
                                        <span>Bill: <?= htmlspecialchars($payment['bill_reference']); ?></span>
                                    <?php elseif ($payment['supplier_name']): ?>
                                        <span>Supplier: <?= htmlspecialchars($payment['supplier_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="payment-method"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></span>
                                </td>
                                <td>
                                    <span class="amount-value">KES <?= number_format((float)$payment['amount'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($payment['status']); ?>">
                                        <?= ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($payment['processed_by_name'])): ?>
                                        <span class="user-name"><?= htmlspecialchars($payment['processed_by_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
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
.payments-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.payments-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.payments-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.payments-filters {
    margin-bottom: 2rem;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
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

.payments-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
}

.kpi-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.kpi-primary .kpi-icon {
    background: rgba(138, 106, 63, 0.1);
    color: var(--primary);
}

.kpi-card:not(.kpi-primary) .kpi-icon {
    background: #f1f5f9;
    color: #64748b;
}

.kpi-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
    min-width: 0;
}

.kpi-label {
    font-size: 0.875rem;
    color: #64748b;
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.payments-table-section {
    margin-top: 2rem;
}

.payments-table-wrapper {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
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

.time {
    font-size: 0.875rem;
    color: #64748b;
}

.reference {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--primary);
}

.payment-type {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.payment-type.type-expense {
    background: #fef3c7;
    color: #f59e0b;
}

.payment-type.type-bill {
    background: #dbeafe;
    color: #3b82f6;
}

.payment-type.type-supplier {
    background: #dcfce7;
    color: #16a34a;
}

.payment-type.type-other {
    background: #f3f4f6;
    color: #6b7280;
}

.payment-method {
    color: var(--dark);
}

.amount-value {
    font-weight: 600;
    color: var(--dark);
    font-size: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.status-completed {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.status-pending {
    background: #fef3c7;
    color: #f59e0b;
}

.status-badge.status-failed {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.status-cancelled {
    background: #f3f4f6;
    color: #6b7280;
}

.user-name {
    color: var(--dark);
}

.text-muted {
    color: #94a3b8;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-state svg {
    margin: 0 auto 1.5rem;
    color: #cbd5e1;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.empty-state p {
    margin: 0 0 1.5rem 0;
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
    .payments-header {
        flex-direction: column;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .payments-kpis {
        grid-template-columns: 1fr;
    }

    .modern-table {
        font-size: 0.875rem;
    }

    .modern-table th,
    .modern-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

