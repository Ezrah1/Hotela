<?php
use App\Support\Auth;

$pageTitle = 'Order Status Overview | Kitchen';
$roleConfig = config('roles', [])[Auth::role()] ?? [];

ob_start();
?>
<div class="order-status-overview">
    <div class="status-header">
        <h2>Order Status Overview</h2>
        <p>Track all kitchen orders by their current status</p>
    </div>

    <!-- Status Summary -->
    <div class="status-summary-grid">
        <div class="status-summary-card status-pending">
            <div class="summary-icon">⏳</div>
            <div class="summary-content">
                <div class="summary-count"><?= $statusCounts['pending'] ?? 0 ?></div>
                <div class="summary-label">Pending Orders</div>
            </div>
        </div>
        <div class="status-summary-card status-confirmed">
            <div class="summary-icon">✓</div>
            <div class="summary-content">
                <div class="summary-count"><?= $statusCounts['confirmed'] ?? 0 ?></div>
                <div class="summary-label">Confirmed Orders</div>
            </div>
        </div>
        <div class="status-summary-card status-preparing">
            <div class="summary-icon">👨‍🍳</div>
            <div class="summary-content">
                <div class="summary-count"><?= $statusCounts['preparing'] ?? 0 ?></div>
                <div class="summary-label">In Preparation</div>
            </div>
        </div>
        <div class="status-summary-card status-ready">
            <div class="summary-icon">✅</div>
            <div class="summary-content">
                <div class="summary-count"><?= $statusCounts['ready'] ?? 0 ?></div>
                <div class="summary-label">Ready for Pickup</div>
            </div>
        </div>
    </div>

    <!-- Orders by Status -->
    <div class="orders-by-status">
        <!-- Pending Orders -->
        <div class="status-section">
            <div class="status-section-header status-pending">
                <h3>Pending Orders (<?= count($ordersByStatus['pending']) ?>)</h3>
                <span class="status-badge">Awaiting Confirmation</span>
            </div>
            <div class="orders-list">
                <?php if (empty($ordersByStatus['pending'])): ?>
                    <div class="empty-state-small">
                        <p>No pending orders</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ordersByStatus['pending'] as $order): ?>
                        <?php include view_path('dashboard/kitchen/_order-card.php'); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Confirmed Orders -->
        <div class="status-section">
            <div class="status-section-header status-confirmed">
                <h3>Confirmed Orders (<?= count($ordersByStatus['confirmed']) ?>)</h3>
                <span class="status-badge">Ready to Start</span>
            </div>
            <div class="orders-list">
                <?php if (empty($ordersByStatus['confirmed'])): ?>
                    <div class="empty-state-small">
                        <p>No confirmed orders</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ordersByStatus['confirmed'] as $order): ?>
                        <?php include view_path('dashboard/kitchen/_order-card.php'); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Preparing Orders -->
        <div class="status-section">
            <div class="status-section-header status-preparing">
                <h3>Orders in Preparation (<?= count($ordersByStatus['preparing']) ?>)</h3>
                <span class="status-badge">Currently Cooking</span>
            </div>
            <div class="orders-list">
                <?php if (empty($ordersByStatus['preparing'])): ?>
                    <div class="empty-state-small">
                        <p>No orders in preparation</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ordersByStatus['preparing'] as $order): ?>
                        <?php include view_path('dashboard/kitchen/_order-card.php'); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ready Orders -->
        <div class="status-section">
            <div class="status-section-header status-ready">
                <h3>Ready Orders (<?= count($ordersByStatus['ready']) ?>)</h3>
                <span class="status-badge">Ready for Service</span>
            </div>
            <div class="orders-list">
                <?php if (empty($ordersByStatus['ready'])): ?>
                    <div class="empty-state-small">
                        <p>No ready orders</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ordersByStatus['ready'] as $order): ?>
                        <?php include view_path('dashboard/kitchen/_order-card.php'); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .order-status-overview {
        padding: 1.5rem;
    }

    .status-header {
        margin-bottom: 2rem;
    }

    .status-header h2 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
    }

    .status-header p {
        color: #6b7280;
        margin: 0;
    }

    .status-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .status-summary-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #e5e7eb;
    }

    .status-summary-card.status-pending { border-left-color: #f59e0b; }
    .status-summary-card.status-confirmed { border-left-color: #3b82f6; }
    .status-summary-card.status-preparing { border-left-color: #8b5cf6; }
    .status-summary-card.status-ready { border-left-color: #10b981; }

    .summary-icon {
        font-size: 2.5rem;
        line-height: 1;
    }

    .summary-content {
        flex: 1;
    }

    .summary-count {
        font-size: 2rem;
        font-weight: bold;
        color: #1f2937;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .summary-label {
        font-size: 0.875rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .orders-by-status {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .status-section {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .status-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .status-section-header.status-pending { border-bottom-color: #f59e0b; }
    .status-section-header.status-confirmed { border-bottom-color: #3b82f6; }
    .status-section-header.status-preparing { border-bottom-color: #8b5cf6; }
    .status-section-header.status-ready { border-bottom-color: #10b981; }

    .status-section-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 1.25rem;
    }

    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-section-header.status-pending .status-badge {
        background: #fef3c7;
        color: #92400e;
    }

    .status-section-header.status-confirmed .status-badge {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-section-header.status-preparing .status-badge {
        background: #ede9fe;
        color: #5b21b6;
    }

    .status-section-header.status-ready .status-badge {
        background: #d1fae5;
        color: #065f46;
    }

    .orders-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .empty-state-small {
        grid-column: 1 / -1;
        text-align: center;
        padding: 2rem;
        color: #9ca3af;
    }

    @media (max-width: 768px) {
        .status-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .orders-list {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

