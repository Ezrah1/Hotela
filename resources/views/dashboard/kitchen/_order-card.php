<div class="order-status-card" data-order-id="<?= $order['id'] ?>" data-status="<?= htmlspecialchars($order['status']) ?>">
    <div class="order-card-header">
        <div class="order-reference"><?= htmlspecialchars($order['reference']) ?></div>
        <div class="order-time"><?= date('H:i', strtotime($order['created_at'])) ?></div>
    </div>
    
    <div class="order-customer">
        <strong><?= htmlspecialchars($order['customer_name'] ?? $order['reservation_guest_name'] ?? 'Walk-in') ?></strong>
        <?php if ($order['room_number']): ?>
            <span class="order-room">Room <?= htmlspecialchars($order['room_number']) ?></span>
        <?php endif; ?>
    </div>

    <div class="order-items">
        <?php if (!empty($order['items'])): ?>
            <ul>
                <?php foreach ($order['items'] as $item): ?>
                    <li>
                        <span class="item-qty"><?= htmlspecialchars($item['quantity']) ?>x</span>
                        <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                        <?php if (!empty($item['special_notes'])): ?>
                            <span class="item-notes">(<?= htmlspecialchars($item['special_notes']) ?>)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No items</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($order['special_instructions'])): ?>
        <div class="order-instructions">
            <strong>Instructions:</strong>
            <p><?= nl2br(htmlspecialchars($order['special_instructions'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="order-actions">
        <?php if ($order['status'] === 'pending' || $order['status'] === 'confirmed'): ?>
            <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'preparing')">
                Start Preparing
            </button>
        <?php endif; ?>
        <?php if ($order['status'] === 'preparing'): ?>
            <button class="btn btn-success btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'ready')">
                Mark Ready
            </button>
        <?php endif; ?>
        <?php if ($order['status'] === 'ready'): ?>
            <button class="btn btn-info btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'delivered')">
                Mark Delivered
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
    .order-status-card {
        background: #f9fafb;
        border-radius: 0.5rem;
        padding: 1rem;
        border-left: 3px solid #e5e7eb;
        transition: all 0.2s;
    }

    .order-status-card[data-status="pending"] { border-left-color: #f59e0b; }
    .order-status-card[data-status="confirmed"] { border-left-color: #3b82f6; }
    .order-status-card[data-status="preparing"] { border-left-color: #8b5cf6; }
    .order-status-card[data-status="ready"] { border-left-color: #10b981; }

    .order-status-card:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .order-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .order-reference {
        font-weight: bold;
        color: #1f2937;
    }

    .order-time {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .order-customer {
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
    }

    .order-customer strong {
        color: #1f2937;
        display: block;
        margin-bottom: 0.25rem;
    }

    .order-room {
        display: block;
        color: #6b7280;
        font-size: 0.75rem;
    }

    .order-items {
        margin-bottom: 0.75rem;
    }

    .order-items ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .order-items li {
        padding: 0.25rem 0;
        font-size: 0.875rem;
        display: flex;
        gap: 0.5rem;
    }

    .item-qty {
        font-weight: bold;
        color: #8a6a3f;
    }

    .item-name {
        flex: 1;
    }

    .item-notes {
        color: #6b7280;
        font-style: italic;
        font-size: 0.75rem;
    }

    .order-instructions {
        background: #fef3c7;
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
    }

    .order-instructions strong {
        display: block;
        margin-bottom: 0.25rem;
        color: #92400e;
    }

    .order-actions {
        display: flex;
        gap: 0.5rem;
    }
</style>

<script>
    function updateOrderStatus(orderId, status) {
        if (!confirm(`Update order status to "${status}"?`)) {
            return;
        }

        fetch('<?= base_url('staff/dashboard/kot/update-status') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                order_id: orderId,
                status: status,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update order status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
    }
</script>

