<?php
$supplier = $supplier ?? [];
$purchaseOrders = $purchaseOrders ?? [];
$performanceHistory = $performanceHistory ?? [];
$stats = $stats ?? [];
$pageTitle = 'Supplier Portal Dashboard | ' . settings('branding.name', 'Hotela');

ob_start();
?>
<section class="page-hero page-hero-simple">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h1>Supplier Portal</h1>
                <p>Welcome, <?= htmlspecialchars($supplier['name'] ?? 'Supplier'); ?></p>
            </div>
            <a href="<?= base_url('supplier/logout'); ?>" class="btn btn-outline">Sign Out</a>
        </div>
    </div>
</section>

<section class="container portal-section">
    <!-- Statistics Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Total Orders</div>
            <div style="font-size: 2rem; font-weight: 700; color: #1e293b;"><?= number_format($stats['total_orders'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Pending Orders</div>
            <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;"><?= number_format($stats['pending_orders'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Completed Orders</div>
            <div style="font-size: 2rem; font-weight: 700; color: #10b981;"><?= number_format($stats['completed_orders'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Unpaid Invoices</div>
            <div style="font-size: 2rem; font-weight: 700; color: #ef4444;"><?= number_format($stats['unpaid_invoices'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;">
            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Total Amount</div>
            <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;">KES <?= number_format($stats['total_amount'] ?? 0, 2); ?></div>
        </div>
    </div>

    <!-- Purchase Orders Section -->
    <div class="card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0;">Purchase Orders</h2>
            <a href="<?= base_url('supplier/purchase-orders'); ?>" class="btn btn-outline">View All</a>
        </div>

        <?php if (empty($purchaseOrders)): ?>
            <p style="color: #64748b; text-align: center; padding: 2rem;">No purchase orders found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">PO Reference</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Date</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Status</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Amount</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Payment</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($purchaseOrders, 0, 10) as $po): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 0.75rem;">
                                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <?= htmlspecialchars($po['reference'] ?? 'PO-' . $po['id']); ?>
                                    </code>
                                </td>
                                <td style="padding: 0.75rem; color: #64748b;">
                                    <?= date('M d, Y', strtotime($po['created_at'] ?? 'now')); ?>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
                                        background: <?= in_array($po['status'] ?? '', ['received', 'completed']) ? '#dcfce7' : ($po['status'] === 'cancelled' ? '#fee2e2' : '#fef3c7'); ?>;
                                        color: <?= in_array($po['status'] ?? '', ['received', 'completed']) ? '#16a34a' : ($po['status'] === 'cancelled' ? '#dc2626' : '#d97706'); ?>;">
                                        <?= ucfirst($po['status'] ?? 'draft'); ?>
                                    </span>
                                </td>
                                <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;">
                                    KES <?= number_format((float)($po['total_amount'] ?? 0), 2); ?>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;
                                        background: <?= ($po['payment_status'] ?? 'unpaid') === 'paid' ? '#dcfce7' : '#fee2e2'; ?>;
                                        color: <?= ($po['payment_status'] ?? 'unpaid') === 'paid' ? '#16a34a' : '#dc2626'; ?>;">
                                        <?= ucfirst($po['payment_status'] ?? 'unpaid'); ?>
                                    </span>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <a href="<?= base_url('supplier/purchase-order?id=' . $po['id']); ?>" class="btn btn-small btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance History -->
    <?php if (!empty($performanceHistory)): ?>
    <div class="card">
        <h2 style="margin: 0 0 1.5rem 0; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">Recent Performance</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Order Date</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Delivery</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">On Time</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performanceHistory as $perf): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; color: #64748b;">
                                <?= date('M d, Y', strtotime($perf['order_date'] ?? 'now')); ?>
                            </td>
                            <td style="padding: 0.75rem; color: #64748b;">
                                <?= $perf['delivery_date'] ? date('M d, Y', strtotime($perf['delivery_date'])) : 'Pending'; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($perf['on_time_delivery']): ?>
                                    <span style="color: #16a34a;">✓ Yes</span>
                                <?php else: ?>
                                    <span style="color: #dc2626;">✗ No</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($perf['total_rating']): ?>
                                    <span style="font-weight: 600; color: #1e293b;">
                                        <?= number_format((float)$perf['total_rating'], 1); ?>/5.0
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</section>

<style>
.portal-section {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
}
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-outline {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #1e293b;
}
.btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/public.php');
?>

