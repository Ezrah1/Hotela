<?php
ob_start();
$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'delivered' => 'Delivered',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$statusBadges = [
    'pending' => 'guest-badge-warning',
    'confirmed' => 'guest-badge-info',
    'preparing' => 'guest-badge-warning',
    'ready' => 'guest-badge-success',
    'delivered' => 'guest-badge-success',
    'completed' => 'guest-badge-success',
    'cancelled' => 'guest-badge-danger',
];
$status = $order['status'] ?? 'pending';
$statusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed'];
$currentStatusIndex = array_search($status, $statusOrder);
?>
<div>
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= base_url('guest/orders'); ?>" class="guest-btn guest-btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            ← Back to Orders
        </a>
        <h1 class="guest-page-title">Order Details</h1>
    </div>

    <div class="guest-card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                    Order #<?= htmlspecialchars($order['reference']); ?>
                </h2>
                <p style="color: var(--guest-text-light); font-size: 0.95rem;">
                    Placed on <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                </p>
            </div>
            <span class="guest-badge <?= $statusBadges[$status] ?? 'guest-badge-info'; ?>">
                <?= $statusLabels[$status] ?? ucfirst($status); ?>
            </span>
        </div>

        <!-- Status Timeline -->
        <div style="margin-bottom: 2rem; padding: 1.5rem; background: var(--guest-bg); border-radius: 0.5rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">Status Timeline</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($statusOrder as $index => $statusItem): ?>
                    <?php if ($statusItem === 'cancelled') continue; ?>
                    <?php
                    $isCompleted = $currentStatusIndex !== false && $index <= $currentStatusIndex;
                    $isCurrent = $status === $statusItem;
                    ?>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $isCompleted ? 'var(--guest-success)' : 'var(--guest-border)'; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?php if ($isCompleted): ?>
                                <span style="color: white; font-size: 0.75rem;">✓</span>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-weight: <?= $isCurrent ? '600' : '400'; ?>; color: <?= $isCompleted ? 'var(--guest-text)' : 'var(--guest-text-light)'; ?>;">
                                <?= $statusLabels[$statusItem]; ?>
                            </p>
                            <?php if ($isCurrent && isset($order['status_logs'])): ?>
                                <?php
                                $statusLog = array_filter($order['status_logs'], fn($log) => $log['status'] === $statusItem);
                                $statusLog = reset($statusLog);
                                ?>
                                <?php if ($statusLog): ?>
                                    <p style="font-size: 0.85rem; color: var(--guest-text-light); margin-top: 0.25rem;">
                                        <?= date('M j, Y g:i A', strtotime($statusLog['created_at'])); ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Items -->
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">Items</h3>
            <div style="display: grid; gap: 0.75rem;">
                <?php foreach ($order['items'] ?? [] as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--guest-bg); border-radius: 0.5rem;">
                        <div>
                            <p style="font-weight: 500; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($item['item_name'] ?? 'Item'); ?>
                            </p>
                            <p style="font-size: 0.85rem; color: var(--guest-text-light);">
                                Qty: <?= number_format((float)($item['quantity'] ?? 1), 0); ?> × KES <?= number_format((float)($item['unit_price'] ?? 0), 2); ?>
                            </p>
                        </div>
                        <p style="font-weight: 600;">
                            KES <?= number_format((float)($item['line_total'] ?? 0), 2); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div style="padding-top: 1.5rem; border-top: 1px solid var(--guest-border);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <p style="font-size: 1.125rem; font-weight: 600;">Total</p>
                <p style="font-size: 1.5rem; font-weight: 600; color: var(--guest-primary);">
                    KES <?= number_format((float)($order['total'] ?? 0), 2); ?>
                </p>
            </div>

            <?php if (!empty($order['notes'])): ?>
                <div style="margin-bottom: 1rem; padding: 1rem; background: var(--guest-bg); border-radius: 0.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--guest-text-light); margin-bottom: 0.5rem;">Notes</h4>
                    <p style="color: var(--guest-text);"><?= nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($status === 'preparing'): ?>
                <div style="padding: 1rem; background: #fef3c7; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <p style="font-size: 0.9rem; color: #92400e;">
                        <strong>Estimated preparation time:</strong> 20-30 minutes
                    </p>
                </div>
            <?php endif; ?>

            <!-- Payment Status -->
            <?php 
            $paymentStatus = $order['payment_status'] ?? 'pending';
            $paymentMethod = $order['payment_type'] ?? '';
            $isPaymentPending = in_array($paymentStatus, ['pending', 'unpaid']);
            $canChangePayment = $isPaymentPending && in_array($paymentMethod, ['cash', 'pay_on_delivery']);
            ?>
            <?php if ($isPaymentPending): ?>
                <div style="padding: 1rem; background: #fef3c7; border-radius: 0.5rem; margin-bottom: 1rem; border-left: 4px solid #f59e0b;">
                    <p style="font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">
                        Payment Status: <span style="text-transform: capitalize;"><?= htmlspecialchars($paymentStatus); ?></span>
                    </p>
                    <p style="font-size: 0.9rem; color: #78350f; margin-bottom: 0.75rem;">
                        Payment Method: <span style="text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $paymentMethod)); ?></span>
                    </p>
                    <?php if ($canChangePayment): ?>
                        <a href="<?= base_url('guest/order/pay?ref=' . urlencode($order['reference'])); ?>" class="guest-btn" style="background: #8b5cf6; color: white; display: inline-block;">
                            💳 Change to Digital Payment
                        </a>
                    <?php else: ?>
                        <p style="font-size: 0.85rem; color: #78350f;">
                            Please complete your payment to proceed with the order.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if (in_array($order['payment_status'] ?? '', ['paid', 'completed'])): ?>
                    <a href="<?= base_url('guest/order?ref=' . urlencode($order['reference']) . '&download=receipt'); ?>" target="_blank" class="guest-btn" style="background: #059669; color: white;">
                        🖨️ Print Receipt
                    </a>
                <?php endif; ?>
                <?php if ($status === 'completed' || $status === 'delivered'): ?>
                    <a href="<?= base_url('order?reorder_ref=' . urlencode($order['reference'])); ?>" class="guest-btn">
                        Reorder
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

