<?php
$pageTitle = 'Edit Role Permissions | Hotela';
ob_start();
?>
<section class="card">
    <header class="role-edit-header">
        <div>
            <h2>Edit Role: <?= htmlspecialchars($role['label'] ?? $roleKey); ?></h2>
            <p class="role-edit-subtitle">Manage permissions for this role</p>
        </div>
        <a class="btn btn-ghost" href="<?= base_url('dashboard/roles'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Roles
        </a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/roles/update'); ?>" class="role-edit-form">
        <input type="hidden" name="role_key" value="<?= htmlspecialchars($roleKey); ?>">

        <div class="form-section">
            <h3 class="section-title">Role Information</h3>
            <div class="role-info-display">
                <div class="info-item">
                    <span class="info-label">Role Key:</span>
                    <span class="info-value"><?= htmlspecialchars($roleKey); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Display Name:</span>
                    <span class="info-value"><?= htmlspecialchars($role['label'] ?? $roleKey); ?></span>
                </div>
                <?php if (!empty($role['dashboard_view'])): ?>
                    <div class="info-item">
                        <span class="info-label">Dashboard View:</span>
                        <span class="info-value"><?= htmlspecialchars($role['dashboard_view']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Permissions</h3>
            <?php if (empty($allPermissions)): ?>
                <div class="empty-permissions">
                    <p>No permissions are defined in the database. Permissions need to be seeded first.</p>
                </div>
            <?php else: ?>
                <div class="permissions-container">
                    <div class="permissions-header">
                        <label class="select-all-checkbox">
                            <input type="checkbox" id="select-all-permissions">
                            <span>Select All</span>
                        </label>
                        <input type="text" id="permission-search" placeholder="Search permissions..." class="permission-search">
                    </div>
                    <div class="permissions-list" id="permissions-list">
                        <?php foreach ($allPermissions as $permission): ?>
                            <?php
                            $isChecked = in_array($permission['key'], $currentPermissions, true);
                            ?>
                            <label class="permission-item" data-permission-key="<?= htmlspecialchars($permission['key']); ?>">
                                <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($permission['key']); ?>" <?= $isChecked ? 'checked' : ''; ?>>
                                <div class="permission-content">
                                    <span class="permission-key"><?= htmlspecialchars($permission['key']); ?></span>
                                    <?php if (!empty($permission['description'])): ?>
                                        <span class="permission-description"><?= htmlspecialchars($permission['description']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Update Permissions
            </button>
            <a class="btn btn-outline" href="<?= base_url('dashboard/roles'); ?>">Cancel</a>
        </div>
    </form>
</section>

<style>
.role-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.role-edit-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.role-edit-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.role-edit-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.section-title {
    margin: 0 0 1.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.role-info-display {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    min-width: 120px;
}

.info-value {
    font-size: 0.95rem;
    color: var(--dark);
    font-family: 'Courier New', monospace;
}

.permissions-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.permissions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.select-all-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.select-all-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.select-all-checkbox span {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
}

.permission-search {
    flex: 1;
    max-width: 300px;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.permission-search:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.permissions-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 0.75rem;
    max-height: 600px;
    overflow-y: auto;
    padding: 0.5rem;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.permission-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.permission-item:hover {
    background: #f1f5f9;
    border-color: var(--primary);
}

.permission-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.permission-item input[type="checkbox"]:checked + .permission-content {
    color: var(--primary);
}

.permission-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.permission-key {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark);
    font-family: 'Courier New', monospace;
}

.permission-description {
    font-size: 0.875rem;
    color: #64748b;
}

.permission-item input[type="checkbox"]:checked ~ .permission-content .permission-key {
    color: var(--primary);
}

.empty-permissions {
    padding: 2rem;
    text-align: center;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.empty-permissions p {
    margin: 0;
    color: #64748b;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.form-actions .btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert {
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.danger {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.permission-item.hidden {
    display: none;
}
</style>

<script>
// Select All functionality
const selectAllCheckbox = document.getElementById('select-all-permissions');
const permissionCheckboxes = document.querySelectorAll('.permission-item input[type="checkbox"]');

selectAllCheckbox?.addEventListener('change', function() {
    permissionCheckboxes.forEach(checkbox => {
        if (!checkbox.closest('.permission-item').classList.contains('hidden')) {
            checkbox.checked = this.checked;
        }
    });
});

// Permission search
const permissionSearch = document.getElementById('permission-search');
const permissionItems = document.querySelectorAll('.permission-item');

permissionSearch?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    
    permissionItems.forEach(item => {
        const permissionKey = item.dataset.permissionKey.toLowerCase();
        const description = item.querySelector('.permission-description')?.textContent.toLowerCase() || '';
        
        if (permissionKey.includes(searchTerm) || description.includes(searchTerm)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

