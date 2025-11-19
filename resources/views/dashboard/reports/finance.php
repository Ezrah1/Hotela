<?php
$pageTitle = 'Finance Reports | Hotela';
$posSummary = $posSummary ?? ['orders' => 0, 'revenue' => 0, 'avg_order' => 0];
$bookingRevenue = $bookingRevenue ?? ['count' => 0, 'total' => 0, 'avg_amount' => 0, 'paid_total' => 0, 'partial_total' => 0, 'unpaid_total' => 0];
$posPayments = $posPayments ?? [];
$bookingPayments = $bookingPayments ?? [];
$posTrend = $posTrend ?? [];
$outstandingBalance = $outstandingBalance ?? 0;
$totalRevenue = $totalRevenue ?? 0;
$totalBookings = $totalBookings ?? 0;
$totalOrders = $totalOrders ?? 0;
$filters = $filters ?? ['start' => date('Y-m-01'), 'end' => date('Y-m-d')];

$dateRangeLabel = date('M j, Y', strtotime($filters['start'])) . ' - ' . date('M j, Y', strtotime($filters['end']));

ob_start();
?>
<section class="card">
    <header class="finance-header">
        <div>
            <h2>Finance Reports</h2>
            <p class="finance-subtitle">Comprehensive financial overview for <?= htmlspecialchars($dateRangeLabel); ?></p>
        </div>
    </header>

    <form method="get" action="<?= base_url('staff/dashboard/reports/finance'); ?>" class="finance-filters">
        <div class="filter-grid">
            <label>
                <span>Start Date</span>
                <input type="date" name="start" value="<?= htmlspecialchars($filters['start']); ?>" class="modern-input">
            </label>
            <label>
                <span>End Date</span>
                <input type="date" name="end" value="<?= htmlspecialchars($filters['end']); ?>" class="modern-input">
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/reports/finance?start=' . urlencode(date('Y-m-01')) . '&end=' . urlencode(date('Y-m-d'))); ?>">This Month</a>
                <a class="btn btn-outline" href="<?= base_url('staff/dashboard/reports/finance?start=' . urlencode(date('Y-m-d', strtotime('-6 days'))) . '&end=' . urlencode(date('Y-m-d'))); ?>">Last 7 Days</a>
            </div>
        </div>
    </form>

    <!-- Summary KPIs -->
    <div class="finance-kpis">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Revenue</span>
                <span class="kpi-value">KES <?= number_format($totalRevenue, 2); ?></span>
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
                <span class="kpi-label">Booking Revenue</span>
                <span class="kpi-value">KES <?= number_format($bookingRevenue['total'], 2); ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">POS Revenue</span>
                <span class="kpi-value">KES <?= number_format($posSummary['revenue'] ?? 0, 2); ?></span>
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
                <span class="kpi-label">Outstanding Balance</span>
                <span class="kpi-value">KES <?= number_format($outstandingBalance, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="finance-sections">
        <div class="finance-section">
            <h3 class="section-title">Booking Revenue Breakdown</h3>
            <div class="stats-grid-small">
                <div class="stat-item">
                    <span class="stat-label">Total Bookings</span>
                    <span class="stat-value"><?= number_format($totalBookings); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Average Booking Value</span>
                    <span class="stat-value">KES <?= number_format($bookingRevenue['avg_amount'], 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Paid</span>
                    <span class="stat-value stat-success">KES <?= number_format($bookingRevenue['paid_total'], 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Partial</span>
                    <span class="stat-value stat-warning">KES <?= number_format($bookingRevenue['partial_total'], 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Unpaid</span>
                    <span class="stat-value stat-danger">KES <?= number_format($bookingRevenue['unpaid_total'], 2); ?></span>
                </div>
            </div>

            <?php if (!empty($bookingPayments)): ?>
                <div class="table-wrapper" style="margin-top: 1.5rem;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Payment Status</th>
                                <th>Bookings</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookingPayments as $payment): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($payment['payment_status']); ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_status']))); ?>
                                        </span>
                                    </td>
                                    <td><?= number_format((int)$payment['count']); ?></td>
                                    <td><strong>KES <?= number_format((float)$payment['total'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="finance-section">
            <h3 class="section-title">POS Sales Breakdown</h3>
            <div class="stats-grid-small">
                <div class="stat-item">
                    <span class="stat-label">Total Orders</span>
                    <span class="stat-value"><?= number_format($totalOrders); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Average Order Value</span>
                    <span class="stat-value">KES <?= number_format($posSummary['avg_order'] ?? 0, 2); ?></span>
                </div>
            </div>

            <?php if (!empty($posPayments)): ?>
                <div class="table-wrapper" style="margin-top: 1.5rem;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>Orders</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posPayments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst($payment['payment_type'])); ?></td>
                                    <td><?= number_format((int)$payment['orders']); ?></td>
                                    <td><strong>KES <?= number_format((float)$payment['total'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daily Trend -->
    <?php if (!empty($posTrend)): ?>
        <div class="finance-section full-width">
            <h3 class="section-title">Daily Revenue Trend</h3>
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>POS Orders</th>
                            <th>POS Revenue</th>
                            <th>Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $maxTotal = max(array_column($posTrend, 'total')) ?: 1;
                        foreach ($posTrend as $day):
                            $percent = min(100, round(((float)$day['total'] / $maxTotal) * 100));
                        ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M j, Y', strtotime($day['day']))); ?></td>
                                <td><?= number_format((int)$day['orders']); ?></td>
                                <td><strong>KES <?= number_format((float)$day['total'], 2); ?></strong></td>
                                <td>
                                    <div class="trend-bar">
                                        <div class="trend-bar-fill" style="width: <?= $percent; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<style>
.finance-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.finance-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.finance-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.finance-filters {
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

.modern-input {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.modern-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.finance-kpis {
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
    border-color: #f59e0b;
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

.finance-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.finance-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.finance-section.full-width {
    grid-column: 1 / -1;
}

.section-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.stats-grid-small {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.875rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--dark);
}

.stat-value.stat-success {
    color: #16a34a;
}

.stat-value.stat-warning {
    color: #f59e0b;
}

.stat-value.stat-danger {
    color: #dc2626;
}

.table-wrapper {
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

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.status-paid {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.status-partial {
    background: #fef3c7;
    color: #f59e0b;
}

.status-badge.status-unpaid {
    background: #fee2e2;
    color: #dc2626;
}

.trend-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}

.trend-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #a67c52);
    border-radius: 999px;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .finance-kpis {
        grid-template-columns: 1fr;
    }

    .finance-sections {
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
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

