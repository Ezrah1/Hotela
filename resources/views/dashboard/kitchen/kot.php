<?php
use App\Support\Auth;

$pageTitle = 'Kitchen Order Tickets (KOT)';
$roleConfig = config('roles', [])[Auth::role()] ?? [];

ob_start();
?>
<div class="kot-dashboard">
        <!-- Status Summary Cards -->
        <div class="kot-status-cards">
            <div class="status-card status-pending" data-status="pending">
                <div class="status-card__count"><?= $statusCounts['pending'] ?? 0 ?></div>
                <div class="status-card__label">Pending</div>
            </div>
            <div class="status-card status-confirmed" data-status="confirmed">
                <div class="status-card__count"><?= $statusCounts['confirmed'] ?? 0 ?></div>
                <div class="status-card__label">Confirmed</div>
            </div>
            <div class="status-card status-preparing" data-status="preparing">
                <div class="status-card__count"><?= $statusCounts['preparing'] ?? 0 ?></div>
                <div class="status-card__label">Preparing</div>
            </div>
            <div class="status-card status-ready" data-status="ready">
                <div class="status-card__count"><?= $statusCounts['ready'] ?? 0 ?></div>
                <div class="status-card__label">Ready</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="kot-filters">
            <button class="kot-filter-btn <?= !$currentStatus ? 'active' : '' ?>" data-status="">
                All Orders
            </button>
            <button class="kot-filter-btn <?= $currentStatus === 'pending' ? 'active' : '' ?>" data-status="pending">
                Pending (<?= $statusCounts['pending'] ?? 0 ?>)
            </button>
            <button class="kot-filter-btn <?= $currentStatus === 'confirmed' ? 'active' : '' ?>" data-status="confirmed">
                Confirmed (<?= $statusCounts['confirmed'] ?? 0 ?>)
            </button>
            <button class="kot-filter-btn <?= $currentStatus === 'preparing' ? 'active' : '' ?>" data-status="preparing">
                Preparing (<?= $statusCounts['preparing'] ?? 0 ?>)
            </button>
            <button class="kot-filter-btn <?= $currentStatus === 'ready' ? 'active' : '' ?>" data-status="ready">
                Ready (<?= $statusCounts['ready'] ?? 0 ?>)
            </button>
        </div>

        <!-- Orders List -->
        <div class="kot-orders-list" id="kot-orders-grid">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 12l2 2 4-4"></path>
                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                    </svg>
                    <h3>No orders found</h3>
                    <p>There are no kitchen orders at this time.</p>
                </div>
            <?php else: ?>
                <table class="kot-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Items</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr class="kot-table-row" data-order-id="<?= $order['id'] ?>" data-status="<?= htmlspecialchars($order['status']) ?>">
                                <td class="kot-reference">
                                    <strong><?= htmlspecialchars($order['reference']) ?></strong>
                                </td>
                                <td class="kot-customer">
                                    <?= htmlspecialchars($order['customer_name'] ?? $order['reservation_guest_name'] ?? 'Walk-in') ?>
                                </td>
                                <td class="kot-room">
                                    <?php if ($order['room_number']): ?>
                                        Room <?= htmlspecialchars($order['room_number']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="kot-items">
                                    <?php if (!empty($order['items'])): ?>
                                        <ul class="items-list">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <li>
                                                    <span class="item-qty"><?= htmlspecialchars($item['quantity']) ?>x</span>
                                                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                    <?php if (!empty($item['special_notes'])): ?>
                                                        <span class="item-notes" title="<?= htmlspecialchars($item['special_notes']) ?>">*</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if (!empty($order['special_instructions'])): ?>
                                            <div class="special-instructions" title="<?= htmlspecialchars($order['special_instructions']) ?>">
                                                <small>📝 Special Instructions</small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No items</span>
                                    <?php endif; ?>
                                </td>
                                <td class="kot-time">
                                    <?= date('H:i', strtotime($order['created_at'])) ?>
                                </td>
                                <td class="kot-status">
                                    <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td class="kot-actions">
                                    <?php if ($order['status'] === 'pending' || $order['status'] === 'confirmed'): ?>
                                        <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'preparing')">
                                            Start
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'preparing'): ?>
                                        <button class="btn btn-success btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'ready')">
                                            Ready
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'ready'): ?>
                                        <button class="btn btn-info btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'delivered')">
                                            Delivered
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Inventory Alerts -->
        <?php if (!empty($inventoryAlerts)): ?>
            <div class="kot-alerts">
                <h3>Inventory Alerts</h3>
                <ul>
                    <?php foreach ($inventoryAlerts as $alert): ?>
                        <li class="alert-item"><?= htmlspecialchars($alert) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .kot-dashboard {
            padding: 1.5rem;
        }

        .kot-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .status-card.status-pending { border-left: 4px solid #f59e0b; }
        .status-card.status-confirmed { border-left: 4px solid #3b82f6; }
        .status-card.status-preparing { border-left: 4px solid #8b5cf6; }
        .status-card.status-ready { border-left: 4px solid #10b981; }

        .status-card__count {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .status-card__label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .kot-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .kot-filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .kot-filter-btn:hover {
            background: #f9fafb;
        }

        .kot-filter-btn.active {
            background: #8a6a3f;
            color: #fff;
            border-color: #8a6a3f;
        }

        .kot-orders-list {
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .kot-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kot-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        .kot-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kot-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .kot-table tbody tr:hover {
            background: #f9fafb;
        }

        .kot-table tbody tr:last-child {
            border-bottom: none;
        }

        .kot-table tbody tr[data-status="pending"] {
            border-left: 4px solid #f59e0b;
        }

        .kot-table tbody tr[data-status="confirmed"] {
            border-left: 4px solid #3b82f6;
        }

        .kot-table tbody tr[data-status="preparing"] {
            border-left: 4px solid #8b5cf6;
        }

        .kot-table tbody tr[data-status="ready"] {
            border-left: 4px solid #10b981;
        }

        .kot-table td {
            padding: 1rem;
            vertical-align: top;
        }

        .kot-reference {
            font-weight: 600;
            color: #1f2937;
        }

        .kot-customer {
            color: #4b5563;
        }

        .kot-room {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .kot-items {
            min-width: 200px;
        }

        .items-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .items-list li {
            padding: 0.25rem 0;
            font-size: 0.875rem;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .item-qty {
            font-weight: bold;
            color: #8a6a3f;
            min-width: 2rem;
        }

        .item-name {
            flex: 1;
        }

        .item-notes {
            color: #f59e0b;
            cursor: help;
            font-weight: bold;
        }

        .special-instructions {
            margin-top: 0.5rem;
            color: #92400e;
            font-size: 0.75rem;
        }

        .kot-time {
            color: #6b7280;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .kot-status {
            white-space: nowrap;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.status-preparing {
            background: #ede9fe;
            color: #5b21b6;
        }

        .status-badge.status-ready {
            background: #d1fae5;
            color: #065f46;
        }

        .kot-actions {
            white-space: nowrap;
        }

        .kot-alerts {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .kot-alerts h3 {
            margin: 0 0 1rem 0;
            color: #1f2937;
        }

        .kot-alerts ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .alert-item {
            padding: 0.75rem;
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state svg {
            margin: 0 auto 1rem;
            color: #d1d5db;
        }

        @media (max-width: 768px) {
            .kot-orders-list {
                overflow-x: auto;
            }

            .kot-table {
                min-width: 800px;
            }

            .kot-status-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .kot-table th,
            .kot-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>

    <script>
        // Auto-refresh every 10 seconds
        let refreshInterval = setInterval(refreshOrders, 10000);

        function refreshOrders() {
            const status = document.querySelector('.kot-filter-btn.active')?.dataset.status || '';
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', '1');
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }

            fetch(url.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateOrdersGrid(data.orders);
                        updateStatusCounts(data.status_counts);
                    }
                })
                .catch(error => console.error('Error refreshing orders:', error));
        }

        function updateOrdersGrid(orders) {
            const container = document.getElementById('kot-orders-grid');
            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                            <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                        </svg>
                        <h3>No orders found</h3>
                        <p>There are no kitchen orders at this time.</p>
                    </div>
                `;
                return;
            }

            const formatTime = (dateString) => {
                const date = new Date(dateString);
                return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            };

            container.innerHTML = `
                <table class="kot-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Items</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders.map(order => `
                            <tr class="kot-table-row" data-order-id="${order.id}" data-status="${order.status}">
                                <td class="kot-reference">
                                    <strong>${escapeHtml(order.reference)}</strong>
                                </td>
                                <td class="kot-customer">
                                    ${escapeHtml(order.customer_name || order.reservation_guest_name || 'Walk-in')}
                                </td>
                                <td class="kot-room">
                                    ${order.room_number ? `Room ${escapeHtml(order.room_number)}` : '<span class="text-muted">-</span>'}
                                </td>
                                <td class="kot-items">
                                    ${order.items && order.items.length > 0 ? `
                                        <ul class="items-list">
                                            ${order.items.map(item => `
                                                <li>
                                                    <span class="item-qty">${item.quantity}x</span>
                                                    <span class="item-name">${escapeHtml(item.item_name)}</span>
                                                    ${item.special_notes ? `<span class="item-notes" title="${escapeHtml(item.special_notes)}">*</span>` : ''}
                                                </li>
                                            `).join('')}
                                        </ul>
                                        ${order.special_instructions ? `
                                            <div class="special-instructions" title="${escapeHtml(order.special_instructions)}">
                                                <small>📝 Special Instructions</small>
                                            </div>
                                        ` : ''}
                                    ` : '<span class="text-muted">No items</span>'}
                                </td>
                                <td class="kot-time">
                                    ${formatTime(order.created_at)}
                                </td>
                                <td class="kot-status">
                                    <span class="status-badge status-${order.status}">
                                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                                    </span>
                                </td>
                                <td class="kot-actions">
                                    ${order.status === 'pending' || order.status === 'confirmed' ? `
                                        <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(${order.id}, 'preparing')">
                                            Start
                                        </button>
                                    ` : ''}
                                    ${order.status === 'preparing' ? `
                                        <button class="btn btn-success btn-sm" onclick="updateOrderStatus(${order.id}, 'ready')">
                                            Ready
                                        </button>
                                    ` : ''}
                                    ${order.status === 'ready' ? `
                                        <button class="btn btn-info btn-sm" onclick="updateOrderStatus(${order.id}, 'delivered')">
                                            Delivered
                                        </button>
                                    ` : ''}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function updateStatusCounts(counts) {
            document.querySelectorAll('.status-card').forEach(card => {
                const status = card.dataset.status;
                const count = counts[status] || 0;
                card.querySelector('.status-card__count').textContent = count;
            });

            document.querySelectorAll('.kot-filter-btn').forEach(btn => {
                const status = btn.dataset.status;
                if (status) {
                    const count = counts[status] || 0;
                    const text = btn.textContent.replace(/\(\d+\)/, `(${count})`);
                    btn.textContent = text;
                }
            });
        }

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
                        refreshOrders();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update order status'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating order status');
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Filter buttons
        document.querySelectorAll('.kot-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.dataset.status;
                const url = new URL(window.location.href);
                if (status) {
                    url.searchParams.set('status', status);
                } else {
                    url.searchParams.delete('status');
                }
                window.location.href = url.toString();
            });
        });

        // Status card clicks
        document.querySelectorAll('.status-card').forEach(card => {
            card.addEventListener('click', function() {
                const status = this.dataset.status;
                const url = new URL(window.location.href);
                if (status) {
                    url.searchParams.set('status', status);
                } else {
                    url.searchParams.delete('status');
                }
                window.location.href = url.toString();
            });
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(refreshInterval);
        });
    </script>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

