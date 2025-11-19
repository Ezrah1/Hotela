<?php
$pageTitle = 'Payments & Transactions | Hotela';
$payments = $payments ?? [];
$summary = $summary ?? ['total' => 0, 'count' => 0, 'by_method' => [], 'by_source' => []];
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d'), 'type' => '', 'payment_method' => ''];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));

ob_start();
?>
<section class="card">
    <header class="payments-header">
        <div>
            <h2>Payments & Transactions</h2>
            <p class="payments-subtitle">View all payment transactions for <?= htmlspecialchars($dateRangeLabel); ?></p>
        </div>
        <div class="header-actions">
            <?php 
            $user = \App\Support\Auth::user();
            $userRole = $user['role_key'] ?? $user['role'] ?? '';
            if (in_array($userRole, ['admin', 'finance_manager'])): 
            ?>
                <a href="<?= base_url('staff/dashboard/payments/record'); ?>" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Record Payment
                </a>
                <a href="<?= base_url('staff/dashboard/payments/manage'); ?>" class="btn btn-outline">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    Manage Payments
                </a>
            <?php endif; ?>
        </div>
    </header>

    <form method="get" action="<?= base_url('staff/dashboard/payments'); ?>" class="payments-filters">
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
                <span>Source</span>
                <select name="type" class="modern-select">
                    <option value="">All Sources</option>
                    <option value="pos" <?= $filters['type'] === 'pos' ? 'selected' : ''; ?>>POS Only</option>
                    <option value="booking" <?= $filters['type'] === 'booking' ? 'selected' : ''; ?>>Bookings Only</option>
                </select>
            </label>
            <label>
                <span>Payment Method</span>
                <select name="payment_method" class="modern-select">
                    <option value="">All Methods</option>
                    <option value="cash" <?= $filters['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="mpesa" <?= $filters['payment_method'] === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                    <option value="card" <?= $filters['payment_method'] === 'card' ? 'selected' : ''; ?>>Card</option>
                    <option value="room" <?= $filters['payment_method'] === 'room' ? 'selected' : ''; ?>>Room Charge</option>
                    <option value="corporate" <?= $filters['payment_method'] === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/payments?start=' . urlencode(date('Y-m-01')) . '&end=' . urlencode(date('Y-m-d'))); ?>">This Month</a>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/payments?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 Days</a>
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
                <span class="kpi-value">KES <?= number_format($summary['total'], 2); ?></span>
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
                <span class="kpi-value"><?= number_format($summary['count']); ?></span>
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
                <span class="kpi-label">POS Payments</span>
                <span class="kpi-value">KES <?= number_format($summary['by_source']['pos'] ?? 0, 2); ?></span>
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
                <span class="kpi-label">Booking Payments</span>
                <span class="kpi-value">KES <?= number_format($summary['by_source']['booking'] ?? 0, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <?php if (!empty($summary['by_method'])): ?>
        <div class="payment-methods-breakdown">
            <h3 class="section-title">Payment Methods Breakdown</h3>
            <div class="methods-grid">
                <?php foreach ($summary['by_method'] as $method => $amount): ?>
                    <div class="method-card">
                        <span class="method-label"><?= htmlspecialchars(ucfirst($method)); ?></span>
                        <span class="method-amount">KES <?= number_format($amount, 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payments List -->
    <div class="payments-section">
        <h3 class="section-title">Transaction History</h3>
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <h3>No Payments Found</h3>
                <p>No payment transactions match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="payments-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Reference</th>
                            <th>Source</th>
                            <th>Description</th>
                            <th>Guest/Customer</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <div class="date-time">
                                        <span class="date"><?= date('M j, Y', strtotime($payment['created_at'])); ?></span>
                                        <span class="time"><?= date('g:i A', strtotime($payment['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="reference"><?= htmlspecialchars($payment['reference']); ?></span>
                                </td>
                                <td>
                                    <span class="source-badge source-<?= $payment['source']; ?>">
                                        <?= strtoupper($payment['source']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="description"><?= htmlspecialchars($payment['description'] ?? 'Payment'); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($payment['guest_name'])): ?>
                                        <div class="guest-info">
                                            <span class="guest-name"><?= htmlspecialchars($payment['guest_name']); ?></span>
                                            <?php if (!empty($payment['reservation_reference'])): ?>
                                                <span class="reservation-ref"><?= htmlspecialchars($payment['reservation_reference']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="payment-method-badge method-<?= strtolower($payment['payment_type']); ?>">
                                        <?= htmlspecialchars(ucfirst($payment['payment_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="amount-value">KES <?= number_format((float)$payment['amount'], 2); ?></span>
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

.payments-kpis {
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

.payment-methods-breakdown {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.method-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.method-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
}

.method-amount {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
}

.payments-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.payments-table-wrapper {
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

.time {
    font-size: 0.875rem;
    color: #64748b;
}

.reference {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary);
}

.source-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.source-badge.source-pos {
    background: #dbeafe;
    color: #2563eb;
}

.source-badge.source-booking {
    background: #dcfce7;
    color: #16a34a;
}

.description {
    color: var(--dark);
}

.guest-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.guest-name {
    font-weight: 600;
    color: var(--dark);
}

.reservation-ref {
    font-size: 0.875rem;
    color: #64748b;
    font-family: 'Courier New', monospace;
}

.payment-method-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.payment-method-badge.method-cash {
    background: #fef3c7;
    color: #f59e0b;
}

.payment-method-badge.method-mpesa {
    background: #dbeafe;
    color: #2563eb;
}

.payment-method-badge.method-card {
    background: #e0e7ff;
    color: #6366f1;
}

.payment-method-badge.method-room {
    background: #fce7f3;
    color: #ec4899;
}

.payment-method-badge.method-corporate {
    background: #f3e8ff;
    color: #9333ea;
}

.amount-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary);
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

@media (max-width: 768px) {
    .payments-kpis {
        grid-template-columns: 1fr;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        width: 100%;
    }

    .filter-actions .btn {
        flex: 1;
    }

    .payments-table-wrapper {
        overflow-x: scroll;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

