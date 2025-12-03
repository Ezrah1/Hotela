<?php
ob_start();
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/portal'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Dashboard
        </a>
        <h1 class="guest-page-title">Active Orders</h1>
        <p class="guest-page-subtitle">Track your food and drink orders</p>
    </div>

    <div class="guest-card">
        <?php if (empty($activeOrders)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">🍽️</div>
                <p>No active orders</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--guest-text-light);">
                    You don't have any active food or drink orders
                </p>
                <a href="<?= base_url('guest/orders'); ?>" class="guest-btn" style="margin-top: 1rem;">
                    View All Orders
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($activeOrders as $order): ?>
                    <div style="padding: 1.5rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Order #<?= htmlspecialchars($order['reference']); ?>
                                </h3>
                                <p style="color: var(--guest-text-light); font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <strong><?= count($order['items'] ?? []); ?> items</strong> • KES <?= number_format((float)($order['total'] ?? 0), 2); ?>
                                </p>
                                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                                    Placed on <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                                <?php if ($order['room_number']): ?>
                                    <p style="color: var(--guest-text-light); font-size: 0.9rem; margin-top: 0.25rem;">
                                        Room <?= htmlspecialchars($order['room_number']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php
                            $statusBadge = 'guest-badge-info';
                            $statusText = ucfirst($order['status'] ?? 'pending');
                            if ($order['status'] === 'ready') $statusBadge = 'guest-badge-success';
                            if ($order['status'] === 'preparing') $statusBadge = 'guest-badge-warning';
                            if ($order['status'] === 'delivered') $statusBadge = 'guest-badge-success';
                            ?>
                            <span class="guest-badge <?= $statusBadge; ?>"><?= $statusText; ?></span>
                        </div>

                        <!-- Quick Items Preview -->
                        <?php if (!empty($order['items'])): ?>
                            <div style="margin-bottom: 1rem; padding: 1rem; background: white; border-radius: 0.5rem;">
                                <p style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Items</p>
                                <div style="display: grid; gap: 0.5rem;">
                                    <?php foreach (array_slice($order['items'], 0, 3) as $item): ?>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                                            <span><?= htmlspecialchars($item['item_name'] ?? 'Item'); ?></span>
                                            <span style="color: var(--guest-text-light);">
                                                <?= number_format((float)($item['quantity'] ?? 1), 0); ?> × KES <?= number_format((float)($item['unit_price'] ?? 0), 2); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($order['items']) > 3): ?>
                                        <p style="font-size: 0.85rem; color: var(--guest-text-light); margin-top: 0.25rem;">
                                            +<?= count($order['items']) - 3; ?> more item<?= count($order['items']) - 3 !== 1 ? 's' : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'preparing'): ?>
                            <div style="padding: 1rem; background: #fef3c7; border-radius: 0.5rem; margin-bottom: 1rem;">
                                <p style="font-size: 0.9rem; color: #92400e;">
                                    <strong>⏱️ Estimated preparation time:</strong> 20-30 minutes
                                </p>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference'])); ?>" class="guest-btn">
                                View Full Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

