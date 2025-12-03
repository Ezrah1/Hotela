<?php
// Order card partial - used for AJAX rendering
$order = $order ?? [];
?>
<div class="order-card" data-order-id="<?= (int)$order['id']; ?>" data-status="<?= htmlspecialchars($order['status']); ?>">
    <div class="order-header">
        <div class="order-info">
            <h3 class="order-reference">
                <?= htmlspecialchars($order['reference']); ?>
                <span class="order-type-badge order-type-<?= htmlspecialchars($order['order_type']); ?>">
                    <?= ucfirst(str_replace('_', ' ', $order['order_type'])); ?>
                </span>
            </h3>
            <div class="order-meta">
                <span class="order-time">
                    <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                </span>
                <?php
                $createdTime = strtotime($order['created_at']);
                $currentTime = time();
                $timeDiff = $currentTime - $createdTime;
                $hours = floor($timeDiff / 3600);
                $minutes = floor(($timeDiff % 3600) / 60);
                $timeAgo = '';
                if ($hours > 0) {
                    $timeAgo = $hours . 'h ' . $minutes . 'm ago';
                } elseif ($minutes > 0) {
                    $timeAgo = $minutes . 'm ago';
                } else {
                    $timeAgo = 'Just now';
                }
                
                $statusTime = null;
                if (!empty($order['status_logs'])) {
                    foreach (array_reverse($order['status_logs']) as $log) {
                        if ($log['status'] === $order['status']) {
                            $statusTime = strtotime($log['created_at']);
                            break;
                        }
                    }
                }
                if (!$statusTime) {
                    $statusTime = $createdTime;
                }
                $statusTimeDiff = $currentTime - $statusTime;
                $statusHours = floor($statusTimeDiff / 3600);
                $statusMinutes = floor(($statusTimeDiff % 3600) / 60);
                $statusTimeAgo = '';
                if ($statusHours > 0) {
                    $statusTimeAgo = $statusHours . 'h ' . $statusMinutes . 'm';
                } elseif ($statusMinutes > 0) {
                    $statusTimeAgo = $statusMinutes . 'm';
                } else {
                    $statusTimeAgo = '< 1m';
                }
                ?>
                <span class="order-divider">•</span>
                <span class="order-age" title="Order created <?= $timeAgo; ?>">
                    <?= $timeAgo; ?>
                </span>
                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                    <span class="order-divider">•</span>
                    <span class="status-duration" title="In <?= ucfirst($order['status']); ?> status for <?= $statusTimeAgo; ?>">
                        <?= ucfirst($order['status']); ?>: <?= $statusTimeAgo; ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($order['user_name'])): ?>
                    <span class="order-divider">•</span>
                    <span class="order-user"><?= htmlspecialchars($order['user_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($order['room_number'])): ?>
                    <span class="order-divider">•</span>
                    <span class="order-room">Room <?= htmlspecialchars($order['room_number']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="order-status-section">
            <div class="order-status-badge status-<?= htmlspecialchars($order['status']); ?>">
                <?= ucfirst(htmlspecialchars($order['status'])); ?>
            </div>
            <div class="order-amount">
                <span class="amount-value">KES <?= number_format((float)($order['total'] ?? 0), 2); ?></span>
                <span class="payment-badge payment-<?= htmlspecialchars($order['payment_type'] ?? 'cash'); ?>">
                    <?= ucfirst(htmlspecialchars($order['payment_type'] ?? 'cash')); ?>
                </span>
            </div>
            <?php 
            // Show payment confirmation button if payment is pending
            $showConfirmButton = false;
            $paymentMethod = $order['payment_type'] ?? '';
            $paymentStatus = $order['payment_status'] ?? 'pending';
            
            if (in_array($paymentStatus, ['pending', 'unpaid'])) {
                // For M-Pesa, check if it's already auto-confirmed
                if ($paymentMethod === 'mpesa' && !empty($order['sale'])) {
                    $sale = $order['sale'];
                    $isMpesaConfirmed = (($sale['mpesa_status'] ?? '') === 'completed' || ($sale['payment_status'] ?? '') === 'paid');
                    $showConfirmButton = !$isMpesaConfirmed;
                } else {
                    // For cash/pay on delivery or other methods, always show button if unpaid
                    $showConfirmButton = true;
                }
            }
            ?>
            <?php if ($showConfirmButton): ?>
                <div style="margin-top: 0.5rem;">
                    <form method="post" action="<?= base_url('staff/dashboard/orders/confirm-payment'); ?>" style="display: inline;" onsubmit="return confirm('Confirm payment received for <?= htmlspecialchars($order['reference']); ?>?');">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                        <input type="hidden" name="reference" value="<?= htmlspecialchars($order['reference']); ?>">
                        <button type="submit" class="btn btn-success btn-small" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            ✓ Confirm Payment
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($order['customer_name']) || !empty($order['notes']) || !empty($order['items'])): ?>
        <div class="order-details">
            <?php if (!empty($order['customer_name'])): ?>
                <div class="order-guest">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><?= htmlspecialchars($order['customer_name']); ?></span>
                    <?php if (!empty($order['customer_phone'])): ?>
                        <span class="order-phone"><?= htmlspecialchars($order['customer_phone']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['items'])): ?>
                <div class="order-items-preview">
                    <strong><?= count($order['items']); ?> item(s):</strong>
                    <?php
                    $itemNames = array_slice(array_column($order['items'], 'item_name'), 0, 3);
                    echo htmlspecialchars(implode(', ', $itemNames));
                    if (count($order['items']) > 3) {
                        echo ' +' . (count($order['items']) - 3) . ' more';
                    }
                    ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['notes'])): ?>
                <div class="order-notes">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
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
        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
            <div class="quick-actions">
                <?php
                $workflow = [
                    'pending' => ['confirmed', 'cancelled'],
                    'confirmed' => ['preparing', 'cancelled'],
                    'preparing' => ['ready', 'cancelled'],
                    'ready' => ['delivered', 'cancelled'],
                    'delivered' => ['completed'],
                ];
                $nextStatuses = $workflow[$order['status']] ?? [];
                $hasCancel = in_array('cancelled', $nextStatuses);
                
                foreach ($nextStatuses as $nextStatus):
                    $buttonClass = $nextStatus === 'cancelled' ? 'btn-quick-action btn-cancel' : 'btn-quick-action';
                    $buttonText = $nextStatus === 'cancelled' ? 'Cancel' : ucfirst($nextStatus);
                ?>
                    <button type="button" 
                            class="<?= $buttonClass; ?>" 
                            data-order-id="<?= (int)$order['id']; ?>"
                            data-status="<?= htmlspecialchars($nextStatus); ?>">
                        <?= htmlspecialchars($buttonText); ?>
                    </button>
                <?php endforeach; ?>
                <?php if (!$hasCancel): ?>
                    <button type="button" 
                            class="btn-quick-action btn-cancel" 
                            data-order-id="<?= (int)$order['id']; ?>"
                            data-status="cancelled">
                        Cancel
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

