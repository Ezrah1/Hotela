<style>
.cashier-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.quick-actions .btn {
    padding: 0.875rem 1.25rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.2s;
}

.quick-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #8a6a3f 0%, #b8945f 100%);
}

.stat-card h4 {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .value {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    line-height: 1.2;
}

.stat-card .trend {
    font-size: 0.75rem;
    color: #64748b;
}

.flex-panels {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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

.dashboard-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-card ul li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-card ul li:last-child {
    border-bottom: none;
}

.dashboard-card ul li strong {
    color: #1e293b;
    font-size: 0.875rem;
    margin-right: 0.5rem;
}

.dashboard-card ul li span {
    color: #64748b;
    font-size: 0.75rem;
    text-align: right;
}

.dashboard-card ul li a {
    color: #8a6a3f;
    text-decoration: none;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.dashboard-card ul li a:hover {
    text-decoration: underline;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #64748b;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .flex-panels {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="cashier-dashboard">
    <!-- Quick Action Buttons -->
    <section class="quick-actions">
        <?php foreach ($dashboardData['shortcuts'] as $shortcut): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($shortcut['action']); ?>">
                <?= htmlspecialchars($shortcut['label']); ?>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Today's Statistics -->
    <?php if (!empty($dashboardData['today_stats'])): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Today's Sales</h4>
                <p class="value"><?= format_currency($dashboardData['today_stats']['total_revenue']); ?></p>
                <span class="trend"><?= number_format($dashboardData['today_stats']['total_orders']); ?> orders</span>
            </div>
            <div class="stat-card">
                <h4>Average Order</h4>
                <p class="value"><?= format_currency($dashboardData['today_stats']['avg_order']); ?></p>
                <span class="trend">Per transaction</span>
            </div>
            <div class="stat-card">
                <h4>POS Sales Today</h4>
                <p class="value"><?= format_currency($dashboardData['pos_summary']['today_total'] ?? 0); ?></p>
                <span class="trend"><?= number_format($dashboardData['pos_summary']['today_count'] ?? 0); ?> tickets</span>
            </div>
            <div class="stat-card">
                <h4>Active Staff</h4>
                <p class="value"><?= number_format($dashboardData['today_stats']['active_staff']); ?></p>
                <span class="trend">Processing sales</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Panels -->
    <div class="flex-panels">
        <!-- Pending Payments -->
        <article class="dashboard-card">
            <h3>Pending Payments</h3>
            <?php if (!empty($dashboardData['pending_payments'])): ?>
                <ul>
                    <?php foreach ($dashboardData['pending_payments'] as $payment): ?>
                        <?php 
                        $balance = (float)($payment['balance'] ?? 0);
                        // Skip negative balances - they shouldn't appear in pending payments
                        if ($balance <= 0) continue;
                        ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($payment['guest'] ?? $payment['guest_name'] ?? 'N/A'); ?></strong>
                                <span><?= htmlspecialchars($payment['room'] ?? $payment['display_name'] ?? $payment['room_number'] ?? 'Unassigned'); ?> · <?= htmlspecialchars(format_currency($balance)); ?></span>
                            </div>
                            <a href="<?= base_url('staff/dashboard/bookings/folio?ref=' . urlencode($payment['reference'] ?? '')); ?>" title="View Folio">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">No pending payments</div>
            <?php endif; ?>
        </article>

        <!-- Arrivals -->
        <article class="dashboard-card">
            <h3>Upcoming Arrivals</h3>
            <?php if (!empty($dashboardData['arrivals'])): ?>
                <ul>
                    <?php foreach ($dashboardData['arrivals'] as $arrival): ?>
                        <?php $dateLabel = !empty($arrival['check_in']) ? date('M j, Y', strtotime($arrival['check_in'])) : 'Pending'; ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($arrival['guest']); ?></strong>
                                <span><?= htmlspecialchars($arrival['room']); ?> · <?= htmlspecialchars($dateLabel); ?></span>
                            </div>
                            <?php if (!empty($arrival['reference'])): ?>
                                <a href="<?= base_url('staff/dashboard/bookings/edit?ref=' . urlencode($arrival['reference'])); ?>" title="View Booking">View</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">No upcoming arrivals</div>
            <?php endif; ?>
        </article>
    </div>

    <!-- Notifications -->
    <article class="dashboard-card">
        <h3>Notifications & Alerts</h3>
        <?php if (!empty($dashboardData['notifications'])): ?>
            <ul>
                <?php foreach ($dashboardData['notifications'] as $note): ?>
                    <li>
                        <span><?= htmlspecialchars($note); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">All clear. No notifications.</div>
        <?php endif; ?>
    </article>
</div>
