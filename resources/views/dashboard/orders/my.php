<?php
$pageTitle = 'My Orders | Hotela';
$orders = $orders ?? [];
$filters = $filters ?? [];
$statusCounts = $statusCounts ?? [];

ob_start();
?>
<section class="card">
    <header class="orders-header">
        <div>
            <h2>My Orders</h2>
            <p class="orders-subtitle">Orders assigned to you</p>
        </div>
        <div class="orders-actions">
            <a href="<?= base_url('staff/dashboard/orders'); ?>" class="btn btn-outline">View All Orders</a>
            <button type="button" class="btn btn-outline" id="refresh-orders">Refresh</button>
        </div>
    </header>

    <!-- Status Counts -->
    <?php if (!empty($statusCounts)): ?>
    <div class="status-counts">
        <?php
        $total = array_sum($statusCounts);
        $statusLabels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'delivered' => 'Delivered',
        ];
        ?>
        <?php foreach ($statusCounts as $status => $count): ?>
            <?php if (isset($statusLabels[$status])): ?>
            <a href="<?= base_url('staff/dashboard/orders/my?status=' . urlencode($status)); ?>" 
               class="status-count-badge <?= $status; ?>">
                <span class="count"><?= $count; ?></span>
                <span class="label"><?= $statusLabels[$status]; ?></span>
            </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($total > 0): ?>
        <div class="status-count-badge total">
            <span class="count"><?= $total; ?></span>
            <span class="label">Total Active</span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Filters & Search Bar -->
    <div class="orders-filters-bar">
        <form method="get" action="<?= base_url('staff/dashboard/orders/my'); ?>" class="filters-form">
            <!-- Search -->
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by ID, name, room, phone..." 
                       value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" 
                       class="filter-input">
            </div>

            <!-- Status Filter -->
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?= ($filters['status'] ?? '') === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?= ($filters['status'] ?? '') === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Order Type Filter -->
            <div class="filter-group">
                <select name="order_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="room_service" <?= ($filters['order_type'] ?? '') === 'room_service' ? 'selected' : ''; ?>>Room Service</option>
                    <option value="restaurant" <?= ($filters['order_type'] ?? '') === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                    <option value="takeaway" <?= ($filters['order_type'] ?? '') === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
                </select>
            </div>

            <!-- Date Filters -->
            <div class="filter-group">
                <input type="date" name="date_from" 
                       value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" 
                       class="filter-input" 
                       placeholder="From Date">
            </div>
            <div class="filter-group">
                <input type="date" name="date_to" 
                       value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" 
                       class="filter-input" 
                       placeholder="To Date">
            </div>

            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if (!empty($filters)): ?>
                <a href="<?= base_url('staff/dashboard/orders/my'); ?>" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
            </svg>
            <h3>No orders found</h3>
            <p>You don't have any orders assigned to you at the moment.</p>
            <a href="<?= base_url('staff/dashboard/orders'); ?>" class="btn btn-primary" style="margin-top: 1rem;">View All Orders</a>
        </div>
    <?php else: ?>
        <div class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Room/Table</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr data-order-id="<?= (int)$order['id']; ?>" class="order-row status-<?= htmlspecialchars($order['status']); ?>">
                        <td>#<?= (int)$order['id']; ?></td>
                        <td>
                            <strong><?= htmlspecialchars($order['reference'] ?? 'N/A'); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></strong>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <br><small><?= htmlspecialchars($order['customer_phone']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-type">
                                <?= ucfirst(str_replace('_', ' ', $order['order_type'] ?? 'restaurant')); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($order['room_number'])): ?>
                                <span class="room-badge">Room <?= htmlspecialchars($order['room_number']); ?></span>
                            <?php elseif (!empty($order['table_number'])): ?>
                                <span class="table-badge">Table <?= htmlspecialchars($order['table_number']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>KES <?= number_format((float)($order['total'] ?? 0), 2); ?></strong>
                        </td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($order['status']); ?>">
                                <?= ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <small><?= !empty($order['created_at']) ? date('M j, Y H:i', strtotime($order['created_at'])) : '—'; ?></small>
                        </td>
                        <td>
                            <div class="order-actions">
                                <a href="<?= base_url('staff/dashboard/orders/show?id=' . (int)$order['id']); ?>" 
                                   class="btn btn-sm btn-outline">View</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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

    // Auto-refresh every 30 seconds
    setInterval(function() {
        const activeFilters = new URLSearchParams(window.location.search);
        if (!activeFilters.has('status') && !activeFilters.has('search')) {
            // Only auto-refresh if no filters are active
            window.location.reload();
        }
    }, 30000);
});
</script>

<style>
/* Orders View Styles */
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

.status-counts {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.status-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.status-count-badge.pending { background: #fef3c7; color: #d97706; }
.status-count-badge.confirmed { background: #dbeafe; color: #2563eb; }
.status-count-badge.preparing { background: #e0e7ff; color: #6366f1; }
.status-count-badge.ready { background: #dcfce7; color: #16a34a; }
.status-count-badge.delivered { background: #cffafe; color: #0891b2; }
.status-count-badge.total { background: #f3f4f6; color: #374151; }

.status-count-badge .count {
    font-size: 1.25rem;
    font-weight: 700;
}

.orders-table-wrapper {
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th {
    text-align: left;
    padding: 1rem;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.orders-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.order-row:hover {
    background: #f9fafb;
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
.status-badge.status-completed { background: #d1fae5; color: #065f46; }
.status-badge.status-cancelled { background: #fee2e2; color: #991b1b; }

.badge-type {
    padding: 0.25rem 0.5rem;
    background: #e5e7eb;
    color: #374151;
    border-radius: 0.25rem;
    font-size: 0.75rem;
}

.room-badge, .table-badge {
    padding: 0.25rem 0.5rem;
    background: #ede9fe;
    color: #7c3aed;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.order-actions {
    display: flex;
    gap: 0.5rem;
}

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

