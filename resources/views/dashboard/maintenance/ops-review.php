<?php
$pageTitle = 'Ops Review - Maintenance Request | Hotela';
$req = $request ?? [];
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Operations Manager Review</h2>
            <p class="maintenance-subtitle">Reference: <code><?= htmlspecialchars($req['reference'] ?? ''); ?></code></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance'); ?>">Back to List</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Request Details -->
    <div class="review-section">
        <h3>Request Details</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Title</span>
                <span class="detail-value"><?= htmlspecialchars($req['title'] ?? ''); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Priority</span>
                <span class="detail-value">
                    <?php
                    $priority = strtolower($req['priority'] ?? 'medium');
                    $priorityColors = [
                        'urgent' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                        'high' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                        'medium' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'low' => ['bg' => '#f3f4f6', 'text' => '#374151'],
                    ];
                    $priorityColor = $priorityColors[$priority] ?? $priorityColors['medium'];
                    ?>
                    <span style="background: <?= $priorityColor['bg']; ?>; color: <?= $priorityColor['text']; ?>; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; text-transform: capitalize;">
                        <?= htmlspecialchars($priority); ?>
                    </span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Room</span>
                <span class="detail-value">
                    <?php if ($req['room_number']): ?>
                        <?= htmlspecialchars($req['room_number']); ?>
                        <?php if ($req['room_name']): ?>
                            (<?= htmlspecialchars($req['room_name']); ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #94a3b8;">General Maintenance</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Requested By</span>
                <span class="detail-value"><?= htmlspecialchars($req['requested_by_name'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <div class="detail-item-full">
            <span class="detail-label">Description</span>
            <p style="white-space: pre-wrap; color: #1e293b; margin-top: 0.5rem;"><?= htmlspecialchars($req['description'] ?? ''); ?></p>
        </div>

        <?php if (!empty($req['materials_needed'])): ?>
        <div class="detail-item-full">
            <span class="detail-label">Materials/Services Needed (from requester)</span>
            <p style="white-space: pre-wrap; color: #1e293b; margin-top: 0.5rem;"><?= htmlspecialchars($req['materials_needed']); ?></p>
        </div>
        <?php endif; ?>

        <?php
        $photos = [];
        if (!empty($req['photos'])) {
            $photos = is_string($req['photos']) ? json_decode($req['photos'], true) : $req['photos'];
            $photos = is_array($photos) ? $photos : [];
        }
        ?>
        <?php if (!empty($photos)): ?>
        <div class="detail-item-full">
            <span class="detail-label">Photos</span>
            <div class="photo-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
                <?php foreach ($photos as $photo): ?>
                    <a href="<?= htmlspecialchars($photo); ?>" target="_blank" style="display: block;">
                        <img src="<?= htmlspecialchars($photo); ?>" alt="Maintenance photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ops Review Form -->
    <form method="post" action="<?= base_url('staff/dashboard/maintenance/ops-review'); ?>" class="review-form">
        <input type="hidden" name="id" value="<?= $req['id']; ?>">

        <div class="form-group">
            <label>
                <span>Ops Notes <span style="color: #dc2626;">*</span></span>
                <small style="color: #64748b; display: block; margin-bottom: 0.5rem;">Describe what must be done, verify the issue, check inventory for parts availability, and ensure the request is legitimate and not duplicated.</small>
                <textarea name="ops_notes" rows="6" required class="modern-input" placeholder="Add your review notes, verification details, and recommendations..."></textarea>
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Cost Estimate (KES)</span>
                    <input type="number" name="cost_estimate" step="0.01" min="0" class="modern-input" placeholder="0.00">
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>
                <span>Required Parts/Materials</span>
                <small style="color: #64748b; display: block; margin-bottom: 0.5rem;">List all parts and materials needed. Check inventory availability.</small>
                <textarea name="materials_needed" rows="4" class="modern-input" placeholder="List required parts, materials, and quantities..."><?= htmlspecialchars($req['materials_needed'] ?? ''); ?></textarea>
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Recommended Suppliers</span>
                <small style="color: #64748b; display: block; margin-bottom: 0.5rem;">Select recommended service providers from the supplier list (internal staff, technicians, or external vendors). You can select multiple.</small>
                <select name="recommended_suppliers" multiple class="modern-select" size="5" style="min-height: 150px;">
                    <?php foreach ($allSuppliers ?? [] as $supplier): ?>
                        <option value="<?= $supplier['id']; ?>">
                            <?= htmlspecialchars($supplier['name']); ?>
                            <?php if (!empty($supplier['contact_person'])): ?>
                                - <?= htmlspecialchars($supplier['contact_person']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #64748b; margin-top: 0.25rem; display: block;">Hold Ctrl (Windows) or Cmd (Mac) to select multiple suppliers</small>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Forward to Finance</button>
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

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.modern-input,
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

.modern-input:focus,
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

