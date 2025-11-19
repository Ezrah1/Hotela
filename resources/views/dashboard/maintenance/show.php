<?php
$pageTitle = 'Maintenance Request Details | Hotela';
$req = $request ?? [];
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Maintenance Request Details</h2>
            <p class="maintenance-subtitle">Reference: <code><?= htmlspecialchars($req['reference'] ?? ''); ?></code></p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance/edit?id=' . $req['id']); ?>">Edit</a>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance'); ?>">Back to List</a>
        </div>
    </header>

    <div class="maintenance-details">
        <div class="detail-section">
            <h3>Request Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Title</span>
                    <span class="detail-value"><?= htmlspecialchars($req['title'] ?? ''); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <?php
                        $status = strtolower($req['status'] ?? 'pending');
                        $statusColors = [
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            'ops_review' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                            'finance_review' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            'approved' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                            'assigned' => ['bg' => '#e0e7ff', 'text' => '#3730a3'],
                            'in_progress' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                            'completed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                            'verified' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                            'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                        ];
                        $statusLabels = [
                            'pending' => 'Pending (Ops Review)',
                            'ops_review' => 'Ops Review',
                            'finance_review' => 'Finance Review',
                            'approved' => 'Approved',
                            'assigned' => 'Assigned',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'verified' => 'Verified',
                            'cancelled' => 'Cancelled',
                        ];
                        $statusColor = $statusColors[$status] ?? $statusColors['pending'];
                        $statusLabel = $statusLabels[$status] ?? str_replace('_', ' ', ucfirst($status));
                        ?>
                        <span style="background: <?= $statusColor['bg']; ?>; color: <?= $statusColor['text']; ?>; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                            <?= htmlspecialchars($statusLabel); ?>
                        </span>
                    </span>
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
                            <span style="color: #94a3b8;">N/A</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($req['description'])): ?>
        <div class="detail-section">
            <h3>Description</h3>
            <p style="white-space: pre-wrap; color: #1e293b;"><?= htmlspecialchars($req['description']); ?></p>
        </div>
        <?php endif; ?>

        <div class="detail-section">
            <h3>Assignment</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Requested By</span>
                    <span class="detail-value"><?= htmlspecialchars($req['requested_by_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Assigned To</span>
                    <span class="detail-value"><?= htmlspecialchars($req['assigned_to_name'] ?? 'Unassigned'); ?></span>
                </div>
            </div>
        </div>

        <?php
        $photos = [];
        if (!empty($req['photos'])) {
            $photos = is_string($req['photos']) ? json_decode($req['photos'], true) : $req['photos'];
            $photos = is_array($photos) ? $photos : [];
        }
        ?>
        <?php if (!empty($photos)): ?>
        <div class="detail-section">
            <h3>Photos</h3>
            <div class="photo-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($photos as $photo): ?>
                    <a href="<?= htmlspecialchars($photo); ?>" target="_blank" style="display: block;">
                        <img src="<?= htmlspecialchars($photo); ?>" alt="Maintenance photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['materials_needed'])): ?>
        <div class="detail-section">
            <h3>Materials/Services Needed</h3>
            <p style="white-space: pre-wrap; color: #1e293b;"><?= htmlspecialchars($req['materials_needed']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['ops_notes'])): ?>
        <div class="detail-section">
            <h3>Ops Manager Notes</h3>
            <p style="white-space: pre-wrap; color: #1e293b; padding: 1rem; background: #eff6ff; border-radius: 0.5rem; border-left: 4px solid #3b82f6;"><?= htmlspecialchars($req['ops_notes']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['finance_notes'])): ?>
        <div class="detail-section">
            <h3>Finance Notes</h3>
            <p style="white-space: pre-wrap; color: #1e293b; padding: 1rem; background: #fffbeb; border-radius: 0.5rem; border-left: 4px solid #f59e0b;"><?= htmlspecialchars($req['finance_notes']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['cost_estimate'])): ?>
        <div class="detail-section">
            <h3>Financial Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Cost Estimate</span>
                    <span class="detail-value" style="font-size: 1.25rem; font-weight: 700; color: #059669;">
                        KES <?= number_format((float)$req['cost_estimate'], 2); ?>
                    </span>
                </div>
                <?php if (!empty($req['approved_by_name'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Approved By</span>
                    <span class="detail-value"><?= htmlspecialchars($req['approved_by_name']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($req['approved_at'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Approved At</span>
                    <span class="detail-value"><?= date('F j, Y g:i A', strtotime($req['approved_at'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['supplier_name'])): ?>
        <div class="detail-section">
            <h3>Supplier Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Supplier</span>
                    <span class="detail-value"><?= htmlspecialchars($req['supplier_name']); ?></span>
                </div>
                <?php if (!empty($req['supplier_contact'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Contact Person</span>
                    <span class="detail-value"><?= htmlspecialchars($req['supplier_contact']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($req['supplier_phone'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($req['supplier_phone']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($req['work_order_reference'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Work Order</span>
                    <span class="detail-value">
                        <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                            <?= htmlspecialchars($req['work_order_reference']); ?>
                        </code>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['notes'])): ?>
        <div class="detail-section">
            <h3>Additional Notes</h3>
            <p style="white-space: pre-wrap; color: #1e293b;"><?= htmlspecialchars($req['notes']); ?></p>
        </div>
        <?php endif; ?>

        <div class="detail-section">
            <h3>Timeline</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Created</span>
                    <span class="detail-value"><?= date('F j, Y g:i A', strtotime($req['created_at'] ?? 'now')); ?></span>
                </div>
                <?php if ($req['completed_at']): ?>
                <div class="detail-item">
                    <span class="detail-label">Completed</span>
                    <span class="detail-value"><?= date('F j, Y g:i A', strtotime($req['completed_at'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $currentUser = \App\Support\Auth::user();
        $canUpdateStatus = in_array($currentUser['role_key'] ?? '', ['admin', 'operation_manager', 'director', 'ground'], true);
        ?>
        <?php if ($canUpdateStatus && $req['status'] !== 'completed' && $req['status'] !== 'cancelled'): ?>
        <div class="detail-section">
            <h3>Update Status</h3>
            <form method="post" action="<?= base_url('staff/dashboard/maintenance/update-status'); ?>" class="status-update-form">
                <input type="hidden" name="id" value="<?= $req['id']; ?>">
                <div class="form-group">
                    <label>
                        <span>New Status</span>
                        <select name="status" required class="modern-select">
                            <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?= $req['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?= $req['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Notes (Optional)</span>
                        <textarea name="notes" rows="3" class="modern-input" placeholder="Add update notes..."></textarea>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.maintenance-details {
    max-width: 900px;
}

.detail-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.detail-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
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

.status-update-form {
    margin-top: 1rem;
}

.form-group {
    margin-bottom: 1rem;
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

.workflow-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

