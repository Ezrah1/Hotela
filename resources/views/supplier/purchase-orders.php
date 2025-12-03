<?php
$purchaseOrders = $purchaseOrders ?? [];
$status = $status ?? '';
$pageTitle = 'Purchase Orders | Supplier Portal | ' . settings('branding.name', 'Hotela');

ob_start();
?>
<section class="page-hero page-hero-simple">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h1>Purchase Orders</h1>
                <p>View and track all your purchase orders</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= base_url('supplier/portal'); ?>" class="btn btn-outline">Back to Dashboard</a>
                <a href="<?= base_url('supplier/logout'); ?>" class="btn btn-outline">Sign Out</a>
            </div>
        </div>
    </div>
</section>

<section class="container portal-section">
    <!-- Filters -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <form method="get" action="<?= base_url('supplier/purchase-orders'); ?>" style="display: flex; gap: 1rem; align-items: center;">
            <select name="status" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.95rem;">
                <option value="">All Statuses</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="sent" <?= $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="in_transit" <?= $status === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                <option value="received" <?= $status === 'received' ? 'selected' : ''; ?>>Received</option>
                <option value="partial" <?= $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($status): ?>
                <a href="<?= base_url('supplier/purchase-orders'); ?>" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Purchase Orders List -->
    <div class="card">
        <?php if (empty($purchaseOrders)): ?>
            <p style="color: #64748b; text-align: center; padding: 2rem;">No purchase orders found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">PO Reference</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Date</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Expected Delivery</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Status</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Amount</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Payment Status</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseOrders as $po): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 0.75rem;">
                                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <?= htmlspecialchars($po['reference'] ?? 'PO-' . $po['id']); ?>
                                    </code>
                                </td>
                                <td style="padding: 0.75rem; color: #64748b;">
                                    <?= date('M d, Y', strtotime($po['created_at'] ?? 'now')); ?>
                                </td>
                                <td style="padding: 0.75rem; color: #64748b;">
                                    <?= $po['expected_date'] ? date('M d, Y', strtotime($po['expected_date'])) : '—'; ?>
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
                                    <a href="<?= base_url('supplier/purchase-order?id=' . $po['id']); ?>" class="btn btn-small btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
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
    border: none;
    cursor: pointer;
}
.btn-primary {
    background: #8b5cf6;
    color: #fff;
}
.btn-primary:hover {
    background: #7c3aed;
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

