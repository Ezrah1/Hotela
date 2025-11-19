<?php
$pageTitle = 'Edit Maintenance Request | Hotela';
$req = $request ?? [];
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Edit Maintenance Request</h2>
            <p class="maintenance-subtitle">Reference: <code><?= htmlspecialchars($req['reference'] ?? ''); ?></code></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance'); ?>">Back to List</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/maintenance/edit'); ?>" class="maintenance-form">
        <input type="hidden" name="id" value="<?= $req['id']; ?>">

        <div class="form-group">
            <label>
                <span>Title <span style="color: #dc2626;">*</span></span>
                <input type="text" name="title" required class="modern-input" value="<?= htmlspecialchars($req['title'] ?? ''); ?>" placeholder="Brief description of the issue">
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Description <span style="color: #dc2626;">*</span></span>
                <textarea name="description" rows="4" required class="modern-input" placeholder="Detailed description of the maintenance issue..."><?= htmlspecialchars($req['description'] ?? ''); ?></textarea>
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Room (Optional - Leave blank for general maintenance)</span>
                    <select name="room_id" class="modern-select">
                        <option value="">No Specific Room</option>
                        <?php foreach ($allRooms ?? [] as $room): ?>
                            <option value="<?= $room['id']; ?>" <?= ($req['room_id'] ?? null) === $room['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($room['room_number']); ?> <?= $room['display_name'] ? '(' . htmlspecialchars($room['display_name']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Priority</span>
                    <select name="priority" class="modern-select">
                        <option value="low" <?= ($req['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?= ($req['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?= ($req['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?= ($req['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Status</span>
                    <select name="status" class="modern-select">
                        <option value="pending" <?= ($req['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?= ($req['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?= ($req['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?= ($req['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Assign To (Optional)</span>
                    <select name="assigned_to" class="modern-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($allStaff ?? [] as $staff): ?>
                            <option value="<?= $staff['id']; ?>" <?= ($req['assigned_to'] ?? null) === $staff['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($staff['name']); ?> (<?= htmlspecialchars($staff['role_key'] ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Request</button>
            <a href="<?= base_url('staff/dashboard/maintenance'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<style>
.maintenance-form {
    max-width: 800px;
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

