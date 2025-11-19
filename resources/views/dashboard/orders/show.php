<?php
$pageTitle = 'Order Details | Hotela';
$order = $order ?? null;
$items = $items ?? [];

if (!$order) {
    http_response_code(404);
    echo 'Order not found';
    return;
}

ob_start();
?>
<section class="card">
    <header class="orders-header">
        <div>
            <a href="<?= base_url('staff/dashboard/orders'); ?>" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Back to Orders
            </a>
            <h2 style="margin-top: 1rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($order['reference']); ?></h2>
            <p class="orders-subtitle"><?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>
        <div class="order-summary-badge">
            <span class="amount-value">KES <?= number_format((float)$order['total'], 2); ?></span>
            <span class="payment-badge payment-<?= htmlspecialchars($order['payment_type']); ?>">
                <?= ucfirst(htmlspecialchars($order['payment_type'])); ?>
            </span>
        </div>
    </header>

    <div class="order-details-grid">
        <div class="order-section">
            <h3>Order Information</h3>
            <div class="info-list">
                <?php if ($order['user_name']): ?>
                    <div class="info-item">
                        <span class="info-label">Staff</span>
                        <span class="info-value"><?= htmlspecialchars($order['user_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['till_name']): ?>
                    <div class="info-item">
                        <span class="info-label">Till</span>
                        <span class="info-value"><?= htmlspecialchars($order['till_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['reservation_guest_name']): ?>
                    <div class="info-item">
                        <span class="info-label">Guest</span>
                        <span class="info-value">
                            <?= htmlspecialchars($order['reservation_guest_name']); ?>
                            <?php if ($order['reservation_reference']): ?>
                                <span class="order-ref">(<?= htmlspecialchars($order['reservation_reference']); ?>)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                    <div class="info-item">
                        <span class="info-label">Notes</span>
                        <span class="info-value"><?= htmlspecialchars($order['notes']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-section">
            <h3>Items</h3>
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="item-info">
                            <span class="item-name"><?= htmlspecialchars($item['item_name']); ?></span>
                            <span class="item-quantity">× <?= (int)$item['quantity']; ?></span>
                        </div>
                        <span class="item-total">KES <?= number_format((float)$item['line_total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="order-total">
                <span class="total-label">Total</span>
                <span class="total-value">KES <?= number_format((float)$order['total'], 2); ?></span>
            </div>
        </div>
    </div>
</section>

<style>
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: var(--primary);
}

.order-summary-badge {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.order-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.order-section h3 {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    min-width: 80px;
}

.info-value {
    font-size: 0.95rem;
    color: var(--dark);
    text-align: right;
    flex: 1;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.item-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.item-name {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--dark);
}

.item-quantity {
    font-size: 0.875rem;
    color: #64748b;
    padding: 0.125rem 0.5rem;
    background: #e2e8f0;
    border-radius: 0.25rem;
}

.item-total {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
}

.order-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--accent-soft);
    border-radius: 0.5rem;
    border: 1px solid rgba(138, 106, 63, 0.2);
}

.total-label {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
}

.total-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
}

@media (max-width: 768px) {
    .order-details-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .order-summary-badge {
        align-items: flex-start;
        margin-top: 1rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

