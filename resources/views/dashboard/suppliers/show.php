<?php
$pageTitle = 'Supplier Details | Hotela';
$supplier = $supplier ?? [];
$purchaseOrders = $purchaseOrders ?? [];

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <a href="<?= base_url('dashboard/suppliers'); ?>" class="back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Suppliers
            </a>
            <div class="header-content">
                <div>
                    <h2><?= htmlspecialchars($supplier['name']); ?></h2>
                    <p class="page-subtitle">Supplier account details and purchase order history</p>
                </div>
                <div class="header-actions">
                    <a href="<?= base_url('dashboard/suppliers/edit?id=' . $supplier['id']); ?>" class="btn btn-outline">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Supplier
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="supplier-details-grid">
        <div class="details-card">
            <h3 class="card-title">Contact Information</h3>
            <div class="details-list">
                <?php if (!empty($supplier['contact_person'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Contact Person</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['contact_person']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['email'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <a href="mailto:<?= htmlspecialchars($supplier['email']); ?>" class="detail-value"><?= htmlspecialchars($supplier['email']); ?></a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['phone'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <a href="tel:<?= htmlspecialchars($supplier['phone']); ?>" class="detail-value"><?= htmlspecialchars($supplier['phone']); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="details-card">
            <h3 class="card-title">Address</h3>
            <div class="details-list">
                <?php if (!empty($supplier['address'])): ?>
                    <div class="detail-item">
                        <span class="detail-value"><?= nl2br(htmlspecialchars($supplier['address'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['city']) || !empty($supplier['country'])): ?>
                    <div class="detail-item">
                        <span class="detail-value">
                            <?= htmlspecialchars(trim(($supplier['city'] ?? '') . ', ' . ($supplier['country'] ?? ''), ', ')); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="details-card">
            <h3 class="card-title">Business Details</h3>
            <div class="details-list">
                <?php if (!empty($supplier['tax_id'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Tax ID</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['tax_id']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['payment_terms'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Payment Terms</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['payment_terms']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="supplier-status status-<?= $supplier['status']; ?>">
                        <?= ucfirst($supplier['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="details-card">
            <h3 class="card-title">Payment Details</h3>
            <div class="details-list">
                <?php if (!empty($supplier['bank_name'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Bank Name</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['bank_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['bank_account_number'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Account Number</span>
                        <span class="detail-value" style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($supplier['bank_account_number']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['bank_branch'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Branch</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['bank_branch']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['bank_swift_code'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">SWIFT Code</span>
                        <span class="detail-value" style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($supplier['bank_swift_code']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($supplier['payment_methods'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Payment Methods</span>
                        <span class="detail-value"><?= htmlspecialchars($supplier['payment_methods']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <span class="detail-label">Credit Limit</span>
                    <span class="detail-value">KES <?= number_format((float)($supplier['credit_limit'] ?? 0), 2); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Current Balance</span>
                    <span class="detail-value <?= (float)($supplier['current_balance'] ?? 0) > 0 ? 'balance-outstanding' : ''; ?>">
                        KES <?= number_format((float)($supplier['current_balance'] ?? 0), 2); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($supplier['notes'])): ?>
            <div class="details-card full-width">
                <h3 class="card-title">Notes</h3>
                <p class="notes-content"><?= nl2br(htmlspecialchars($supplier['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="purchase-orders-section">
        <h3 class="section-title">Purchase Orders</h3>
        <?php if (empty($purchaseOrders)): ?>
            <div class="empty-state-small">
                <p>No purchase orders found for this supplier.</p>
            </div>
        <?php else: ?>
            <div class="po-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>PO ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseOrders as $po): ?>
                            <tr>
                                <td>#<?= $po['id']; ?></td>
                                <td><?= date('M j, Y', strtotime($po['created_at'])); ?></td>
                                <td>
                                    <span class="po-status status-<?= strtolower($po['status']); ?>">
                                        <?= ucfirst($po['status']); ?>
                                    </span>
                                </td>
                                <td><?= number_format($po['item_count'] ?? 0); ?></td>
                                <td>KES <?= number_format($po['total_amount'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: var(--primary);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.page-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.page-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.supplier-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.details-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.details-card.full-width {
    grid-column: 1 / -1;
}

.card-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.details-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: 0.95rem;
    color: var(--dark);
    font-weight: 500;
}

.detail-value a {
    color: var(--primary);
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

.supplier-status {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.supplier-status.status-active {
    background: #dcfce7;
    color: #16a34a;
}

.supplier-status.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

.notes-content {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}

.purchase-orders-section {
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.po-table-wrapper {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.95rem;
    color: var(--dark);
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

.po-status {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.po-status.status-draft {
    background: #f1f5f9;
    color: #64748b;
}

.po-status.status-sent {
    background: #dbeafe;
    color: #2563eb;
}

.po-status.status-received {
    background: #dcfce7;
    color: #16a34a;
}

.po-status.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.empty-state-small {
    text-align: center;
    padding: 3rem 2rem;
    color: #64748b;
}

.balance-outstanding {
    color: #dc2626;
    font-weight: 600;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
    }

    .supplier-details-grid {
        grid-template-columns: 1fr;
    }

    .po-table-wrapper {
        overflow-x: scroll;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

