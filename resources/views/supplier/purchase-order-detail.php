<?php
$purchaseOrder = $purchaseOrder ?? [];
$items = $items ?? [];
$pageTitle = 'Purchase Order Details | Supplier Portal | ' . settings('branding.name', 'Hotela');

ob_start();
?>
<section class="page-hero page-hero-simple">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h1>Purchase Order Details</h1>
                <p>PO Reference: <code><?= htmlspecialchars($purchaseOrder['reference'] ?? 'PO-' . $purchaseOrder['id']); ?></code></p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= base_url('supplier/purchase-orders'); ?>" class="btn btn-outline">Back to Orders</a>
                <a href="<?= base_url('supplier/logout'); ?>" class="btn btn-outline">Sign Out</a>
            </div>
        </div>
    </div>
</section>

<section class="container portal-section">
    <div class="card">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Status</div>
                <div>
                    <span style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;
                        background: <?= in_array($purchaseOrder['status'] ?? '', ['received', 'completed']) ? '#dcfce7' : ($purchaseOrder['status'] === 'cancelled' ? '#fee2e2' : '#fef3c7'); ?>;
                        color: <?= in_array($purchaseOrder['status'] ?? '', ['received', 'completed']) ? '#16a34a' : ($purchaseOrder['status'] === 'cancelled' ? '#dc2626' : '#d97706'); ?>;">
                        <?= ucfirst($purchaseOrder['status'] ?? 'draft'); ?>
                    </span>
                </div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Order Date</div>
                <div style="font-weight: 600; color: #1e293b;">
                    <?= date('M d, Y', strtotime($purchaseOrder['created_at'] ?? 'now')); ?>
                </div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Expected Delivery</div>
                <div style="font-weight: 600; color: #1e293b;">
                    <?= $purchaseOrder['expected_date'] ? date('M d, Y', strtotime($purchaseOrder['expected_date'])) : 'Not specified'; ?>
                </div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Payment Status</div>
                <div>
                    <span style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;
                        background: <?= ($purchaseOrder['payment_status'] ?? 'unpaid') === 'paid' ? '#dcfce7' : '#fee2e2'; ?>;
                        color: <?= ($purchaseOrder['payment_status'] ?? 'unpaid') === 'paid' ? '#16a34a' : '#dc2626'; ?>;">
                        <?= ucfirst($purchaseOrder['payment_status'] ?? 'unpaid'); ?>
                    </span>
                </div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Total Amount</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                    KES <?= number_format((float)($purchaseOrder['total_amount'] ?? 0), 2); ?>
                </div>
            </div>
        </div>

        <h3 style="margin: 0 0 1rem 0; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">Order Items</h3>
        <?php if (empty($items)): ?>
            <p style="color: #64748b;">No items found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Item</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Quantity</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #1e293b;">Unit Cost</th>
                            <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #1e293b;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0;
                        foreach ($items as $item): 
                            $lineTotal = (float)($item['quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0);
                            $grandTotal += $lineTotal;
                        ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 0.75rem;">
                                    <strong><?= htmlspecialchars($item['name'] ?? 'Item'); ?></strong>
                                    <?php if (!empty($item['unit'])): ?>
                                        <br><small style="color: #64748b;">Unit: <?= htmlspecialchars($item['unit']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.75rem; color: #64748b;">
                                    <?= number_format((float)($item['quantity'] ?? 0), 2); ?>
                                </td>
                                <td style="padding: 0.75rem; color: #64748b;">
                                    KES <?= number_format((float)($item['unit_cost'] ?? 0), 2); ?>
                                </td>
                                <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #1e293b;">
                                    KES <?= number_format($lineTotal, 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                            <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 700; color: #1e293b;">Grand Total:</td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: #1e293b; font-size: 1.125rem;">
                                KES <?= number_format($grandTotal, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
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
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/public.php');
?>

