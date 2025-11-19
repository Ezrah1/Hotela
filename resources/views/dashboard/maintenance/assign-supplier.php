<?php
$pageTitle = 'Assign Supplier - Maintenance Request | Hotela';
$req = $request ?? [];
$recommendedSupplierIds = $recommendedSupplierIds ?? [];
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Assign Supplier</h2>
            <p class="maintenance-subtitle">Reference: <code><?= htmlspecialchars($req['reference'] ?? ''); ?></code></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance'); ?>">Back to List</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Request Summary -->
    <div class="review-section">
        <h3>Request Summary</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Title</span>
                <span class="detail-value"><?= htmlspecialchars($req['title'] ?? ''); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Cost Estimate</span>
                <span class="detail-value" style="font-size: 1.25rem; font-weight: 700; color: #059669;">
                    KES <?= number_format((float)($req['cost_estimate'] ?? 0), 2); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($req['ops_notes'])): ?>
        <div class="detail-item-full">
            <span class="detail-label">Ops Notes</span>
            <p style="white-space: pre-wrap; color: #1e293b; margin-top: 0.5rem; padding: 1rem; background: #fff; border-radius: 0.5rem; border: 1px solid #e2e8f0;"><?= htmlspecialchars($req['ops_notes']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($recommendedSupplierIds)): ?>
        <div class="detail-item-full">
            <span class="detail-label">Recommended Suppliers (by Ops)</span>
            <div style="margin-top: 0.5rem;">
                <?php
                $supplierRepo = new \App\Repositories\SupplierRepository();
                foreach ($recommendedSupplierIds as $supplierId):
                    $supplier = $supplierRepo->find($supplierId);
                    if ($supplier):
                ?>
                    <div style="padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem; border: 1px solid #fcd34d; margin-bottom: 0.5rem;">
                        <strong><?= htmlspecialchars($supplier['name']); ?></strong>
                        <?php if (!empty($supplier['contact_person'])): ?>
                            <br><small style="color: #92400e;">Contact: <?= htmlspecialchars($supplier['contact_person']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($supplier['phone'])): ?>
                            <br><small style="color: #92400e;">Phone: <?= htmlspecialchars($supplier['phone']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($supplier['email'])): ?>
                            <br><small style="color: #92400e;">Email: <?= htmlspecialchars($supplier['email']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Supplier Selection Form -->
    <form method="post" action="<?= base_url('staff/dashboard/maintenance/assign-supplier'); ?>" class="review-form">
        <input type="hidden" name="id" value="<?= $req['id']; ?>">

        <div class="form-group">
            <label>
                <span>Select Supplier/Service Provider <span style="color: #dc2626;">*</span></span>
                <small style="color: #64748b; display: block; margin-bottom: 0.5rem;">Select the supplier or service provider to assign this work. A work order will be automatically generated.</small>
                <select name="supplier_id" required class="modern-select">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($allSuppliers ?? [] as $supplier): ?>
                        <option value="<?= $supplier['id']; ?>" <?= in_array($supplier['id'], $recommendedSupplierIds) ? 'style="background: #fef3c7;"' : ''; ?>>
                            <?= htmlspecialchars($supplier['name']); ?>
                            <?php if (!empty($supplier['contact_person'])): ?>
                                - <?= htmlspecialchars($supplier['contact_person']); ?>
                            <?php endif; ?>
                            <?php if (in_array($supplier['id'], $recommendedSupplierIds)): ?>
                                (Recommended)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Assign Supplier & Create Work Order</button>
            <a href="<?= base_url('staff/dashboard/maintenance'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<style>
.review-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.review-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item-full {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.detail-value {
    font-size: 0.95rem;
    color: #1e293b;
    font-weight: 500;
}

.review-form {
    max-width: 900px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label span {
    font-weight: 600;
    color: #475569;
    font-size: 0.95rem;
}

.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #1e293b;
    font-family: inherit;
}

.modern-select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

