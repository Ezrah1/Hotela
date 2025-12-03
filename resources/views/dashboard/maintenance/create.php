<?php
$pageTitle = 'Create Maintenance Request | Hotela';
ob_start();
?>
<section class="card">
    <header class="maintenance-header">
        <div>
            <h2>Create Maintenance Request</h2>
            <p class="maintenance-subtitle">Submit a new maintenance request</p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/maintenance'); ?>">Back to List</a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-error" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
            <?= htmlspecialchars($_GET['error']); ?>
            <?php if (!empty($_GET['duplicate_id'])): ?>
                <br><a href="<?= base_url('staff/dashboard/maintenance?filter=mine&highlight=' . (int)$_GET['duplicate_id']); ?>" style="color: #dc2626; text-decoration: underline; margin-top: 0.5rem; display: inline-block;">
                    View Existing Request
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/maintenance/create'); ?>" class="maintenance-form" enctype="multipart/form-data">
        <div class="form-group">
            <label>
                <span>Title <span style="color: #dc2626;">*</span></span>
                <input type="text" name="title" required class="modern-input" placeholder="Brief description of the issue">
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Description <span style="color: #dc2626;">*</span></span>
                <textarea name="description" rows="4" required class="modern-input" placeholder="Detailed description of the maintenance issue..."></textarea>
            </label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <span>Room (Optional - Leave blank for general maintenance)</span>
                    <select name="room_id" class="modern-select">
                        <option value="">No Specific Room</option>
                        <?php foreach ($allRooms ?? [] as $room): ?>
                            <option value="<?= $room['id']; ?>">
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
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>
                <span>Assign To (Optional)</span>
                <select name="assigned_to" class="modern-select">
                    <option value="">Unassigned</option>
                    <?php foreach ($allStaff ?? [] as $staff): ?>
                        <option value="<?= $staff['id']; ?>">
                            <?= htmlspecialchars($staff['name']); ?> (<?= htmlspecialchars($staff['role_key'] ?? 'N/A'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Materials or Services Needed (Optional)</span>
                <textarea name="materials_needed" rows="3" class="modern-input" placeholder="List any materials, parts, or services required for this maintenance..."></textarea>
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Photos (Optional)</span>
                <input type="file" name="photos[]" multiple accept="image/*" class="modern-input" id="photo-upload">
                <small style="color: #64748b; margin-top: 0.25rem; display: block;">You can select multiple photos to upload</small>
                <div id="photo-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;"></div>
            </label>
        </div>

        <div class="form-group">
            <label>
                <span>Additional Notes (Optional)</span>
                <textarea name="notes" rows="3" class="modern-input" placeholder="Any additional notes or instructions..."></textarea>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Request</button>
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

#photo-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}
</style>

<script>
document.getElementById('photo-upload').addEventListener('change', function(e) {
    const preview = document.getElementById('photo-preview');
    preview.innerHTML = '';
    
    if (e.target.files.length > 0) {
        Array.from(e.target.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

