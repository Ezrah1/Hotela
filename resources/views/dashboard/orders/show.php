<?php
$pageTitle = 'Order Details | Hotela';
$order = $order ?? null;

if (!$order) {
    header('Location: ' . base_url('staff/dashboard/orders?error=Order%20not%20found'));
    exit;
}

ob_start();
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;
$paymentLink = $_GET['payment_link'] ?? null;
?>
<div class="order-detail-container">
    <?php if ($success): ?>
        <div style="padding: 1rem 1.5rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #16a34a;">
            <strong>✓ <?= htmlspecialchars($success); ?></strong>
            <?php if ($paymentLink): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 0.375rem; border: 1px solid #10b981;">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 600; font-size: 0.875rem; color: #065f46;">Payment Link Generated:</p>
                    <p style="margin: 0 0 1rem 0; font-size: 0.85rem; color: #047857;">
                        The payment request has been sent to the customer. You can also copy the link below if needed.
                    </p>
                    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                        <input type="text" id="paymentLinkInput" value="<?= htmlspecialchars($paymentLink); ?>" readonly style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; font-family: monospace; background: #f8fafc;">
                        <button type="button" onclick="copyPaymentLink()" class="btn btn-primary btn-small" style="white-space: nowrap;">
                            📋 Copy
                        </button>
                    </div>
                    <div style="padding: 0.75rem; background: #f0fdf4; border-radius: 0.375rem; border-left: 3px solid #10b981;">
                        <p style="margin: 0; font-size: 0.8rem; color: #166534; font-weight: 500;">
                            💡 <strong>Tip:</strong> Customers can click this link directly in their email or SMS to complete payment.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="padding: 1rem 1.5rem; background: #fee2e2; color: #991b1b; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #dc2626;">
            <strong>✗ <?= htmlspecialchars($error); ?></strong>
        </div>
    <?php endif; ?>
    <!-- Header -->
    <section class="card">
        <div class="order-detail-header">
            <div>
                <h2>Order <?= htmlspecialchars($order['reference']); ?></h2>
                <div class="order-meta-info">
                    <span class="order-type-badge order-type-<?= htmlspecialchars($order['order_type']); ?>">
                        <?= ucfirst(str_replace('_', ' ', $order['order_type'])); ?>
                    </span>
                    <span class="order-date">Placed: <?= date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
            <div class="order-status-actions">
                <div class="order-status-badge-large status-<?= htmlspecialchars($order['status']); ?>">
                    <?= ucfirst(htmlspecialchars($order['status'])); ?>
                </div>
                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                    <div class="status-update-actions">
                        <?php
                        $nextStatuses = getNextStatuses($order['status']);
                        foreach ($nextStatuses as $nextStatus):
                        ?>
                            <button type="button" class="btn btn-primary btn-small" 
                                    onclick="updateStatus('<?= htmlspecialchars($nextStatus); ?>')">
                                Mark as <?= ucfirst($nextStatus); ?>
                            </button>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-danger btn-small" onclick="cancelOrder()">
                            Cancel Order
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Order Information -->
    <div class="order-detail-grid">
        <!-- Left Column -->
        <div class="order-detail-main">
            <!-- Customer Information -->
            <section class="card">
                <h3>Customer Information</h3>
                <div class="info-grid">
                    <?php if ($order['customer_name']): ?>
                        <div class="info-item">
                            <label>Name</label>
                            <div><?= htmlspecialchars($order['customer_name']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['customer_phone']): ?>
                        <div class="info-item">
                            <label>Phone</label>
                            <div><a href="tel:<?= htmlspecialchars($order['customer_phone']); ?>"><?= htmlspecialchars($order['customer_phone']); ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['customer_email']): ?>
                        <div class="info-item">
                            <label>Email</label>
                            <div><a href="mailto:<?= htmlspecialchars($order['customer_email']); ?>"><?= htmlspecialchars($order['customer_email']); ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['room_number']): ?>
                        <div class="info-item">
                            <label>Room</label>
                            <div>Room <?= htmlspecialchars($order['room_number']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['reservation_reference']): ?>
                        <div class="info-item">
                            <label>Reservation</label>
                            <div>
                                <a href="<?= base_url('staff/dashboard/bookings/folio?reservation_id=' . (int)$order['reservation_id']); ?>">
                                    <?= htmlspecialchars($order['reservation_reference']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Order Items -->
            <section class="card">
                <h3>Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] ?? [] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['item_name']); ?></strong>
                                    <?php if ($item['special_notes']): ?>
                                        <div class="item-notes"><?= htmlspecialchars($item['special_notes']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((float)$item['quantity'], 2); ?></td>
                                <td>KES <?= number_format((float)$item['unit_price'], 2); ?></td>
                                <td><strong>KES <?= number_format((float)$item['line_total'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>KES <?= number_format((float)$order['total'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <!-- Status Timeline -->
            <section class="card">
                <h3>Status Timeline</h3>
                <div class="status-timeline">
                    <?php
                    $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed'];
                    $currentIndex = array_search($order['status'], $statuses);
                    if ($currentIndex === false) $currentIndex = -1;
                    
                    foreach ($statuses as $index => $status):
                        $isActive = $index <= $currentIndex;
                        $logEntry = null;
                        foreach ($order['status_logs'] ?? [] as $log) {
                            if ($log['status'] === $status) {
                                $logEntry = $log;
                                break;
                            }
                        }
                    ?>
                        <div class="timeline-item <?= $isActive ? 'active' : ''; ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-status"><?= ucfirst($status); ?></div>
                                <?php if ($logEntry): ?>
                                    <div class="timeline-meta">
                                        <?= date('M j, Y g:i A', strtotime($logEntry['created_at'])); ?>
                                        <?php if ($logEntry['changed_by_name']): ?>
                                            by <?= htmlspecialchars($logEntry['changed_by_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($logEntry['notes']): ?>
                                        <div class="timeline-notes"><?= htmlspecialchars($logEntry['notes']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="timeline-meta">Pending</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Comments -->
            <section class="card">
                <h3>Internal Comments</h3>
                <div id="comments-list" class="comments-list">
                    <?php foreach ($order['comments'] ?? [] as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <strong><?= htmlspecialchars($comment['user_name'] ?? 'System'); ?></strong>
                                <span class="comment-date"><?= date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-body"><?= nl2br(htmlspecialchars($comment['comment'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form id="add-comment-form" class="add-comment-form">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                    <textarea name="comment" placeholder="Add a comment..." required></textarea>
                    <select name="visibility">
                        <option value="all">Visible to All</option>
                        <option value="kitchen">Kitchen Only</option>
                        <option value="service">Service Only</option>
                        <option value="ops">Operations Only</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Add Comment</button>
                </form>
            </section>
        </div>

        <!-- Right Column -->
        <div class="order-detail-sidebar">
            <!-- Payment Information -->
            <section class="card">
                <h3>Payment</h3>
                <div class="info-item">
                    <label>Status</label>
                    <div class="payment-status-badge payment-<?= htmlspecialchars($order['payment_status']); ?>">
                        <?= ucfirst(htmlspecialchars($order['payment_status'])); ?>
                    </div>
                </div>
                <div class="info-item">
                    <label>Method</label>
                    <div><?= ucfirst(htmlspecialchars($order['payment_type'])); ?></div>
                </div>
                <div class="info-item">
                    <label>Total Amount</label>
                    <div class="amount-large">KES <?= number_format((float)$order['total'], 2); ?></div>
                </div>
                <?php 
                // Show payment confirmation button if payment is pending
                $showConfirmButton = false;
                $paymentMethod = $order['payment_type'] ?? '';
                $paymentStatus = $order['payment_status'] ?? 'pending';
                
                if (in_array($paymentStatus, ['pending', 'unpaid'])) {
                    // For M-Pesa, check if it's already auto-confirmed
                    if ($paymentMethod === 'mpesa' && !empty($sale)) {
                        $isMpesaConfirmed = ($sale['mpesa_status'] === 'completed' || $sale['payment_status'] === 'paid');
                        $showConfirmButton = !$isMpesaConfirmed;
                    } else {
                        // For cash/pay on delivery or other methods, always show button if unpaid
                        $showConfirmButton = true;
                    }
                }
                ?>
                <?php if ($showConfirmButton): ?>
                    <div class="info-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                        <label>Payment Actions</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <?php if ($order['customer_phone'] || $order['customer_email']): ?>
                                <form method="post" action="<?= base_url('staff/dashboard/orders/request-payment'); ?>" style="margin: 0;" onsubmit="return confirm('Send payment request link to customer?');">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                    <input type="hidden" name="reference" value="<?= htmlspecialchars($order['reference']); ?>">
                                    <button type="submit" class="btn btn-primary btn-small" style="width: 100%;">
                                        📧 Request Payment from Customer
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= base_url('staff/dashboard/orders/confirm-payment'); ?>" style="margin: 0;" onsubmit="return confirm('Confirm that payment has been received for this order?');">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                                <input type="hidden" name="reference" value="<?= htmlspecialchars($order['reference']); ?>">
                                <button type="submit" class="btn btn-success btn-small" style="width: 100%;">
                                    ✓ Confirm Payment Received
                                </button>
                            </form>
                        </div>
                        <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">
                            <?php if ($paymentMethod === 'cash' || $paymentMethod === 'pay_on_delivery'): ?>
                                Request payment to send customer a payment link. Mark as received when customer pays on delivery/pickup.
                            <?php else: ?>
                                Request payment to send customer a payment link. Use confirm if payment was received but not automatically confirmed.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Order Details -->
            <section class="card">
                <h3>Order Details</h3>
                <div class="info-item">
                    <label>Source</label>
                    <div><?= ucfirst(str_replace('_', ' ', $order['source'])); ?></div>
                </div>
                <?php if ($order['service_type']): ?>
                    <div class="info-item">
                        <label>Service Type</label>
                        <div><?= ucfirst(str_replace('_', ' ', $order['service_type'])); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['assigned_staff_name']): ?>
                    <div class="info-item">
                        <label>Assigned Staff</label>
                        <div><?= htmlspecialchars($order['assigned_staff_name']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($order['user_name']): ?>
                    <div class="info-item">
                        <label>Created By</label>
                        <div><?= htmlspecialchars($order['user_name']); ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- Processing Times -->
                <?php
                $createdTime = strtotime($order['created_at']);
                $currentTime = time();
                $totalTime = $currentTime - $createdTime;
                $totalHours = floor($totalTime / 3600);
                $totalMinutes = floor(($totalTime % 3600) / 60);
                
                // Calculate time in each status
                $statusTimes = [];
                $statusLogs = $order['status_logs'] ?? [];
                usort($statusLogs, function($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });
                
                $prevTime = $createdTime;
                foreach ($statusLogs as $log) {
                    $logTime = strtotime($log['created_at']);
                    $duration = $logTime - $prevTime;
                    $statusTimes[$log['status']] = $duration;
                    $prevTime = $logTime;
                }
                // Add current status time
                if (!empty($statusLogs)) {
                    $lastLogTime = strtotime(end($statusLogs)['created_at']);
                    $statusTimes[$order['status']] = $currentTime - $lastLogTime;
                } else {
                    $statusTimes[$order['status']] = $currentTime - $createdTime;
                }
                ?>
                <div class="info-item">
                    <label>Total Processing Time</label>
                    <div class="processing-time">
                        <?php if ($totalHours > 0): ?>
                            <?= $totalHours; ?>h <?= $totalMinutes; ?>m
                        <?php else: ?>
                            <?= $totalMinutes; ?>m
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($statusTimes) && $order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                    <div class="info-item">
                        <label>Time in Current Status</label>
                        <div class="current-status-time">
                            <?php
                            $currentStatusTime = $statusTimes[$order['status']] ?? 0;
                            $currentHours = floor($currentStatusTime / 3600);
                            $currentMins = floor(($currentStatusTime % 3600) / 60);
                            ?>
                            <?php if ($currentHours > 0): ?>
                                <?= $currentHours; ?>h <?= $currentMins; ?>m
                            <?php else: ?>
                                <?= $currentMins; ?>m
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Notes -->
            <?php if ($order['notes'] || $order['special_instructions']): ?>
                <section class="card">
                    <h3>Notes</h3>
                    <?php if ($order['notes']): ?>
                        <div class="notes-item">
                            <label>Order Notes</label>
                            <div><?= nl2br(htmlspecialchars($order['notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['special_instructions']): ?>
                        <div class="notes-item">
                            <label>Special Instructions</label>
                            <div><?= nl2br(htmlspecialchars($order['special_instructions'])); ?></div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <!-- Actions -->
            <section class="card">
                <h3>Actions</h3>
                <div class="action-buttons">
                    <a href="<?= base_url('staff/dashboard/orders'); ?>" class="btn btn-outline btn-block">Back to Orders</a>
                    <button type="button" class="btn btn-outline btn-block" onclick="printReceipt()">Print Receipt</button>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
.order-detail-container {
    max-width: 1400px;
    margin: 0 auto;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.order-detail-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.order-detail-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-detail-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
}

.order-items-table th,
.order-items-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.order-items-table th {
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
}

.status-timeline {
    position: relative;
    padding-left: 2rem;
}

.status-timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item.active .timeline-marker {
    background: var(--primary);
    border-color: var(--primary);
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: #e2e8f0;
    border: 2px solid #cbd5e1;
}

.timeline-content {
    padding-left: 1rem;
}

.timeline-status {
    font-weight: 600;
    color: var(--dark);
}

.timeline-meta {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.comments-list {
    margin-bottom: 1rem;
}

.comment-item {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.comment-date {
    color: #64748b;
}

.add-comment-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.add-comment-form textarea {
    min-height: 80px;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.processing-time,
.current-status-time {
    font-weight: 600;
    color: #3b82f6;
    font-size: 1.1rem;
}

.current-status-time {
    color: #16a34a;
}

@media (max-width: 1024px) {
    .order-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function updateStatus(status) {
    if (status === 'cancelled') {
        cancelOrder();
        return;
    }
    
    fetch('<?= base_url('staff/dashboard/orders/update-status'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=<?= (int)$order['id']; ?>&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function cancelOrder() {
    const reason = prompt('Please provide cancellation reason:');
    if (!reason) return;
    
    fetch('<?= base_url('staff/dashboard/orders/cancel'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=<?= (int)$order['id']; ?>&reason=${encodeURIComponent(reason)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function copyPaymentLink() {
    const input = document.getElementById('paymentLinkInput');
    if (input) {
        input.select();
        input.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand('copy');
        alert('Payment link copied to clipboard!');
    }
}

document.getElementById('add-comment-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= base_url('staff/dashboard/orders/add-comment'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

function printReceipt() {
    window.print();
}
</script>

<?php
function getNextStatuses($currentStatus) {
    $workflow = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['delivered', 'cancelled'],
        'delivered' => ['completed'],
    ];
    return $workflow[$currentStatus] ?? [];
}
?>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
