<?php
$pageTitle = 'Order Updates | Hotela';
$orders = $orders ?? [];
$filters = $filters ?? [];

ob_start();
?>
<section class="card">
    <header class="orders-header">
        <div>
            <h2>Order Updates</h2>
            <p class="orders-subtitle">Recent order changes and updates in the last 24 hours</p>
        </div>
        <div class="orders-actions">
            <a href="<?= base_url('staff/dashboard/orders/my'); ?>" class="btn btn-outline">My Orders</a>
            <a href="<?= base_url('staff/dashboard/orders'); ?>" class="btn btn-outline">View All Orders</a>
            <button type="button" class="btn btn-outline" id="refresh-orders">Refresh</button>
        </div>
    </header>

    <!-- Filters -->
    <div class="orders-filters-bar">
        <form method="get" action="<?= base_url('staff/dashboard/orders/updates'); ?>" class="filters-form">
            <!-- Status Filter -->
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?= ($filters['status'] ?? '') === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?= ($filters['status'] ?? '') === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if (!empty($filters)): ?>
                <a href="<?= base_url('staff/dashboard/orders/updates'); ?>" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <h3>No recent updates</h3>
            <p>There are no order updates in the last 24 hours.</p>
        </div>
    <?php else: ?>
        <div class="orders-updates-list">
            <?php foreach ($orders as $order): ?>
                <div class="update-card status-<?= htmlspecialchars($order['status']); ?>" data-order-id="<?= (int)$order['id']; ?>">
                    <div class="update-header">
                        <div>
                            <h3>Order #<?= htmlspecialchars($order['reference'] ?? 'N/A'); ?></h3>
                            <p class="update-meta">
                                <?= htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?>
                                <?php if (!empty($order['room_number'])): ?>
                                    · Room <?= htmlspecialchars($order['room_number']); ?>
                                <?php endif; ?>
                                · <?= ucfirst(str_replace('_', ' ', $order['order_type'] ?? 'restaurant')); ?>
                            </p>
                        </div>
                        <div class="update-status">
                            <span class="status-badge status-<?= htmlspecialchars($order['status']); ?>">
                                <?= ucfirst($order['status']); ?>
                            </span>
                            <strong class="update-total">KES <?= number_format((float)($order['total'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['status_logs'])): ?>
                        <div class="update-timeline">
                            <?php 
                            $recentLogs = array_slice($order['status_logs'], -3); // Show last 3 status changes
                            foreach ($recentLogs as $log): 
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <strong><?= htmlspecialchars($log['status'] ?? ''); ?></strong>
                                        <small>
                                            by <?= htmlspecialchars($log['user_name'] ?? 'System'); ?>
                                            <?php if (!empty($log['created_at'])): ?>
                                                · <?= date('H:i', strtotime($log['created_at'])); ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if (!empty($log['notes'])): ?>
                                            <p class="timeline-notes"><?= htmlspecialchars($log['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="update-footer">
                        <small class="update-time">
                            Last updated: <?= !empty($order['updated_at']) ? date('M j, Y H:i', strtotime($order['updated_at'])) : '—'; ?>
                        </small>
                        <a href="<?= base_url('staff/dashboard/orders/show?id=' . (int)$order['id']); ?>" 
                           class="btn btn-sm btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh button
    const refreshBtn = document.getElementById('refresh-orders');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }

    // Auto-refresh every 15 seconds for updates page
    setInterval(function() {
        window.location.reload();
    }, 15000);
});
</script>

<style>
/* Order Updates Styles */
.orders-updates-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.update-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: all 0.2s;
}

.update-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.update-card.status-ready {
    border-left: 4px solid #16a34a;
}

.update-card.status-preparing {
    border-left: 4px solid #6366f1;
}

.update-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.update-header h3 {
    margin: 0 0 0.25rem;
    font-size: 1.125rem;
    color: #111827;
}

.update-meta {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.update-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.update-total {
    font-size: 1.125rem;
    color: #111827;
}

.update-timeline {
    margin: 1rem 0;
    padding-left: 1.5rem;
    border-left: 2px solid #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 1rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -1.625rem;
    top: 0.25rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6366f1;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-content strong {
    display: block;
    color: #111827;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.timeline-content small {
    color: #6b7280;
    font-size: 0.75rem;
}

.timeline-notes {
    margin: 0.5rem 0 0;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
}

.update-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.update-time {
    color: #6b7280;
}

/* Inherit styles from my.php */
.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.orders-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    color: #111827;
}

.orders-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.orders-actions {
    display: flex;
    gap: 0.75rem;
}

.orders-filters-bar {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.filters-form {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-input, .filter-select {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.status-pending { background: #fef3c7; color: #d97706; }
.status-badge.status-confirmed { background: #dbeafe; color: #2563eb; }
.status-badge.status-preparing { background: #e0e7ff; color: #6366f1; }
.status-badge.status-ready { background: #dcfce7; color: #16a34a; }
.status-badge.status-delivered { background: #cffafe; color: #0891b2; }

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    color: #9ca3af;
}

.empty-state h3 {
    margin: 0 0 0.5rem;
    color: #374151;
}

.empty-state p {
    margin: 0;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

