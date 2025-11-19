<?php
$pageTitle = 'Verify Work - Maintenance Request | Hotela';
$req = $request ?? [];
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Verify Completed Work</h2>
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
        <h3>Work Summary</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Title</span>
                <span class="detail-value"><?= htmlspecialchars($req['title'] ?? ''); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Supplier</span>
                <span class="detail-value"><?= htmlspecialchars($req['supplier_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Work Order</span>
                <span class="detail-value">
                    <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                        <?= htmlspecialchars($req['work_order_reference'] ?? 'N/A'); ?>
                    </code>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Cost Estimate</span>
                <span class="detail-value" style="font-size: 1.25rem; font-weight: 700; color: #059669;">
                    KES <?= number_format((float)($req['cost_estimate'] ?? 0), 2); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($req['completed_at'])): ?>
        <div class="detail-item-full">
            <span class="detail-label">Completed At</span>
            <p style="color: #1e293b; margin-top: 0.5rem;"><?= date('F j, Y g:i A', strtotime($req['completed_at'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Verification Form -->
    <form method="post" action="<?= base_url('staff/dashboard/maintenance/verify-work'); ?>" class="review-form">
        <input type="hidden" name="id" value="<?= $req['id']; ?>">

        <div class="alert alert-info" style="padding: 1rem; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            <strong>Verification Process:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                <li>Inspect the completed work to ensure it meets quality standards</li>
                <li>Verify that all required materials were used correctly</li>
                <li>Confirm the work addresses the original issue</li>
                <li>Once verified, Finance will be notified for payment processing</li>
            </ul>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Verify Work & Mark Ready for Payment</button>
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

