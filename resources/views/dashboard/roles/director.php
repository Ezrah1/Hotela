<style>
.director-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    position: relative;
    overflow: hidden;
    transition: all 0.2s;
}

.kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #8a6a3f 0%, #b8945f 100%);
}

.kpi-card h4 {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-card .value {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    line-height: 1.2;
}

.kpi-card .trend {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
}

.stat-item .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #8a6a3f;
    margin: 0 0 0.25rem 0;
}

.stat-item .stat-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.dashboard-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.dashboard-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 1rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dashboard-card h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: #8a6a3f;
    border-radius: 2px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: #475569;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: #8a6a3f;
    color: #ffffff;
    border-color: #8a6a3f;
    transform: translateY(-1px);
}

.transaction-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.transaction-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-info {
    flex: 1;
}

.transaction-info strong {
    display: block;
    color: #1e293b;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.transaction-info span {
    display: block;
    color: #64748b;
    font-size: 0.75rem;
}

.transaction-amount {
    font-weight: 600;
    color: #059669;
    font-size: 0.875rem;
}

.transaction-amount.pending {
    color: #dc2626;
}

.revenue-chart {
    height: 200px;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.chart-bar {
    flex: 1;
    background: linear-gradient(180deg, #8a6a3f 0%, #b8945f 100%);
    border-radius: 4px 4px 0 0;
    min-height: 20px;
    position: relative;
    transition: all 0.2s;
}

.chart-bar:hover {
    opacity: 0.8;
    transform: scaleY(1.05);
}

.chart-bar::after {
    content: attr(data-value);
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.625rem;
    color: #64748b;
    white-space: nowrap;
}

.chart-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}

.list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.list-item:last-child {
    border-bottom: none;
}

.list-item strong {
    color: #1e293b;
    font-size: 0.875rem;
}

.list-item span {
    color: #64748b;
    font-size: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #64748b;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="director-dashboard">
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <?php foreach ($dashboardData['kpis'] as $kpi): ?>
            <article class="kpi-card">
                <h4><?= htmlspecialchars($kpi['label']); ?></h4>
                <p class="value"><?= htmlspecialchars($kpi['value']); ?></p>
                <span class="trend"><?= htmlspecialchars($kpi['trend']); ?></span>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-overview">
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['total_bookings'] ?? 0); ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['confirmed_bookings'] ?? 0); ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['checked_in'] ?? 0); ?></div>
            <div class="stat-label">Checked In</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['pending_payments'] ?? 0); ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['low_stock_items'] ?? 0); ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['rooms_need_cleaning'] ?? 0); ?></div>
            <div class="stat-label">Rooms Need Cleaning</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['active_staff'] ?? 0); ?></div>
            <div class="stat-label">Active Staff</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= number_format($dashboardData['stats']['unread_notifications'] ?? 0); ?></div>
            <div class="stat-label">Unread Notifications</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-card">
        <h3>Quick Actions</h3>
        <div class="quick-actions">
            <a href="<?= base_url('staff/dashboard/bookings/create'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
                New Booking
            </a>
            <a href="<?= base_url('staff/dashboard/pos'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                POS System
            </a>
            <a href="<?= base_url('staff/dashboard/payments'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Payments
            </a>
            <a href="<?= base_url('staff/dashboard/inventory'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Inventory
            </a>
            <a href="<?= base_url('staff/admin/settings'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                </svg>
                Settings
            </a>
            <a href="<?= base_url('staff/dashboard/reports'); ?>" class="quick-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Reports
            </a>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Revenue Trend -->
        <?php if (!empty($dashboardData['revenue_trend'])): ?>
            <div class="dashboard-card">
                <h3>Revenue Trend (Last 7 Days)</h3>
                <div class="revenue-chart">
                    <?php 
                    $maxRevenue = max(array_column($dashboardData['revenue_trend'], 'revenue')) ?: 1;
                    foreach ($dashboardData['revenue_trend'] as $day): 
                        $height = ($day['revenue'] / $maxRevenue) * 100;
                    ?>
                        <div class="chart-bar" style="height: <?= max(20, $height); ?>%" data-value="<?= format_currency($day['revenue']); ?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-labels">
                    <?php foreach ($dashboardData['revenue_trend'] as $day): ?>
                        <span><?= date('M j', strtotime($day['date'])); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <div class="dashboard-card">
            <h3>Recent Transactions</h3>
            <?php if (!empty($dashboardData['recent_transactions'])): ?>
                <ul class="transaction-list">
                    <?php foreach (array_slice($dashboardData['recent_transactions'], 0, 8) as $transaction): ?>
                        <li class="transaction-item">
                            <div class="transaction-info">
                                <strong><?= htmlspecialchars($transaction['guest_name']); ?></strong>
                                <span><?= htmlspecialchars($transaction['reference']); ?> · <?= date('M j, H:i', strtotime($transaction['created_at'])); ?></span>
                            </div>
                            <div class="transaction-amount <?= $transaction['payment_status'] === 'paid' ? '' : 'pending'; ?>">
                                <?= format_currency($transaction['amount']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="<?= base_url('staff/dashboard/payments'); ?>" class="quick-action-btn" style="display: inline-flex;">
                        View All Transactions
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">No recent transactions</div>
            <?php endif; ?>
        </div>

        <!-- Arrivals -->
        <?php if (!empty($dashboardData['arrivals'])): ?>
            <div class="dashboard-card">
                <h3>Upcoming Arrivals</h3>
                <ul class="transaction-list">
                    <?php foreach ($dashboardData['arrivals'] as $arrival): ?>
                        <?php $room = $arrival['display_name'] ?? $arrival['room_number'] ?? $arrival['room_type_name'] ?? 'Unassigned'; ?>
                        <li class="list-item">
                            <div>
                                <strong><?= htmlspecialchars($arrival['guest_name']); ?></strong>
                                <span><?= htmlspecialchars($room); ?> · <?= date('M j, Y', strtotime($arrival['check_in'])); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Departures -->
        <?php if (!empty($dashboardData['departures'])): ?>
            <div class="dashboard-card">
                <h3>Upcoming Departures</h3>
                <ul class="transaction-list">
                    <?php foreach ($dashboardData['departures'] as $departure): ?>
                        <?php $room = $departure['display_name'] ?? $departure['room_number'] ?? $departure['room_type_name'] ?? 'Unassigned'; ?>
                        <li class="list-item">
                            <div>
                                <strong><?= htmlspecialchars($departure['guest_name']); ?></strong>
                                <span><?= htmlspecialchars($room); ?> · <?= date('M j, Y', strtotime($departure['check_out'])); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Pending Payments -->
        <?php if (!empty($dashboardData['pending_payments'])): ?>
            <?php 
            $pendingTotal = array_sum(array_column($dashboardData['pending_payments'], 'balance'));
            $pendingCount = count($dashboardData['pending_payments']);
            ?>
            <div class="dashboard-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Pending Payments</h3>
                    <div style="text-align: right;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #dc2626;">
                            <?= format_currency($pendingTotal); ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b;">
                            <?= $pendingCount; ?> folio<?= $pendingCount !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>
                <ul class="transaction-list">
                    <?php foreach ($dashboardData['pending_payments'] as $payment): ?>
                        <li class="transaction-item">
                            <div class="transaction-info">
                                <strong><?= htmlspecialchars($payment['guest_name']); ?></strong>
                                <span><?= htmlspecialchars($payment['reference']); ?></span>
                            </div>
                            <div class="transaction-amount pending">
                                <?= format_currency((float)$payment['balance']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <a href="<?= base_url('staff/dashboard/folios'); ?>" class="btn btn-outline btn-small">
                        View All Folios →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Low Stock Items -->
        <?php if (!empty($dashboardData['low_stock'])): ?>
            <div class="dashboard-card">
                <h3>Low Stock Alert</h3>
                <ul class="transaction-list">
                    <?php foreach ($dashboardData['low_stock'] as $item): ?>
                        <li class="list-item">
                            <div>
                                <strong><?= htmlspecialchars($item['name']); ?></strong>
                                <span><?= htmlspecialchars($item['location']); ?> · Stock: <?= number_format($item['quantity'], 2); ?> <?= htmlspecialchars($item['unit']); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Strategic Alerts -->
        <?php if (!empty($dashboardData['alerts'])): ?>
            <div class="dashboard-card">
                <h3>Strategic Alerts</h3>
                <ul class="transaction-list">
                    <?php foreach ($dashboardData['alerts'] as $alert): ?>
                        <li class="list-item">
                            <span><?= htmlspecialchars($alert); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Action Items -->
        <?php if (!empty($dashboardData['actions'])): ?>
            <div class="dashboard-card">
                <h3>Action Items</h3>
                <ul class="transaction-list">
                    <?php foreach (array_filter($dashboardData['actions']) as $action): ?>
                        <li class="list-item">
                            <span><?= htmlspecialchars($action); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
