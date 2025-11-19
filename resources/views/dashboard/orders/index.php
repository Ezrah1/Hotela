<?php
$pageTitle = 'Orders | Hotela';
$orders = $orders ?? [];
$filter = $filter ?? 'today';

ob_start();
?>
<section class="card">
    <header class="orders-header">
        <div>
            <h2>Orders</h2>
            <p class="orders-subtitle">View and manage all restaurant orders</p>
        </div>
        <div class="orders-filters">
            <select id="filter-select" class="filter-select">
                <option value="today" <?= $filter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                <option value="month" <?= $filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                <option value="" <?= $filter === '' ? 'selected' : ''; ?>>All Time</option>
            </select>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12l2 2 4-4"></path>
                <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                <path d="M12 21c0-1-1-3-3-3s-3 2-3 3 1 3 3 3 3-2 3-3z"></path>
                <path d="M12 3c0 1-1 3-3 3s-3-2-3-3 1-3 3-3 3 2 3 3z"></path>
            </svg>
            <h3>No orders found</h3>
            <p>There are no orders for the selected period.</p>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3 class="order-reference"><?= htmlspecialchars($order['reference']); ?></h3>
                            <div class="order-meta">
                                <span class="order-time">
                                    <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </span>
                                <?php if ($order['user_name']): ?>
                                    <span class="order-divider">•</span>
                                    <span class="order-user"><?= htmlspecialchars($order['user_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="order-amount">
                            <span class="amount-value">KES <?= number_format((float)$order['total'], 2); ?></span>
                            <span class="payment-badge payment-<?= htmlspecialchars($order['payment_type']); ?>">
                                <?= ucfirst(htmlspecialchars($order['payment_type'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($order['reservation_guest_name'] || $order['notes']): ?>
                        <div class="order-details">
                            <?php if ($order['reservation_guest_name']): ?>
                                <div class="order-guest">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <span><?= htmlspecialchars($order['reservation_guest_name']); ?></span>
                                    <?php if ($order['reservation_reference']): ?>
                                        <span class="order-ref">(<?= htmlspecialchars($order['reservation_reference']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['notes']): ?>
                                <div class="order-notes">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    <span><?= htmlspecialchars($order['notes']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="order-actions">
                        <a href="<?= base_url('staff/dashboard/orders/show?id=' . (int)$order['id']); ?>" class="btn-view">
                            View Details
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.orders-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.orders-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.orders-filters {
    display: flex;
    gap: 0.75rem;
}

.filter-select {
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--dark);
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.filter-select:hover {
    border-color: var(--primary);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
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

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.order-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1.5rem;
}

.order-info {
    flex: 1;
}

.order-reference {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}

.order-divider {
    color: #cbd5e1;
}

.order-amount {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.amount-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark);
}

.payment-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.payment-cash {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.payment-card {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.payment-mpesa {
    background: rgba(139, 92, 246, 0.15);
    color: #8b5cf6;
}

.payment-room {
    background: rgba(249, 115, 22, 0.15);
    color: #f97316;
}

.order-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.order-guest,
.order-notes {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #475569;
}

.order-guest svg,
.order-notes svg {
    flex-shrink: 0;
    color: #94a3b8;
}

.order-ref {
    color: #94a3b8;
    font-size: 0.8125rem;
}

.order-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-view {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: var(--dark);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-view:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(138, 106, 63, 0.05);
}

.btn-view svg {
    transition: transform 0.2s ease;
}

.btn-view:hover svg {
    transform: translateX(2px);
}

@media (max-width: 768px) {
    .orders-header {
        flex-direction: column;
        gap: 1rem;
    }

    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .order-amount {
        align-items: flex-start;
        width: 100%;
    }
}
</style>

<script>
document.getElementById('filter-select')?.addEventListener('change', function() {
    const filter = this.value;
    const url = new URL(window.location.href);
    if (filter) {
        url.searchParams.set('filter', filter);
    } else {
        url.searchParams.delete('filter');
    }
    window.location.href = url.toString();
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

