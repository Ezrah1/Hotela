<?php
$pageTitle = 'Edit User | Hotela';
ob_start();
?>
<section class="card">
    <header class="user-edit-header">
        <div>
            <h2>Edit User</h2>
            <p class="user-edit-subtitle">Update user information and role</p>
        </div>
        <a class="btn btn-ghost" href="<?= base_url('staff/dashboard/staff'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Staff
        </a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/staff/update?id=' . (int)($user['id'] ?? 0)); ?>" class="user-edit-form" id="staff-edit-form">
        <?php if (empty($user['id'])): ?>
            <div class="alert danger">Error: User ID is missing. Cannot update user.</div>
        <?php else: ?>
            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>" id="user_id_field">
            <!-- Debug: User ID is <?= (int)$user['id']; ?> -->
        <?php endif; ?>

        <div class="form-section">
            <h3 class="section-title">Basic Information</h3>
            <div class="form-grid">
                <label class="form-field-full">
                    <span>Full Name *</span>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required class="modern-input">
                </label>
                <label class="form-field-full">
                    <span>Email Address *</span>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required class="modern-input">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Role & Status</h3>
            <div class="form-grid">
                <?php
                // Get current user's roles
                $userRepository = new \App\Repositories\UserRepository();
                $userRoles = $userRepository->getUserRoles((int)$user['id']);
                $currentRoleKeys = array_column($userRoles, 'role_key');
                $primaryRoleKey = $userRepository->getPrimaryRole((int)$user['id']) ?? $user['role_key'] ?? '';
                
                // Roles that can have multiple assignments
                $multiRoleEligible = ['receptionist', 'cashier', 'service_agent'];
                $isMultiRoleEligible = in_array($primaryRoleKey, $multiRoleEligible, true);
                
                // Filter out admin and director from assignable roles
                $assignableRoles = array_filter($roles, function($role) {
                    return !in_array($role['key'], ['admin', 'director', 'super_admin'], true);
                });
                ?>
                
                <?php if ($isMultiRoleEligible): ?>
                    <label class="form-field-full">
                        <span>Roles *</span>
                        <div class="roles-checkbox-group">
                            <p class="roles-help-text" style="margin: 0 0 0.75rem 0; font-size: 0.875rem; color: #64748b;">
                                Select one or more roles. The first selected role will be the primary role.
                            </p>
                            <div class="roles-checkbox-list">
                                <?php foreach ($assignableRoles as $role): ?>
                                    <?php
                                    $isChecked = in_array($role['key'], $currentRoleKeys, true);
                                    $isPrimary = ($role['key'] === $primaryRoleKey);
                                    ?>
                                    <label class="role-checkbox-item">
                                        <input 
                                            type="checkbox" 
                                            name="roles[]" 
                                            value="<?= htmlspecialchars($role['key']); ?>"
                                            <?= $isChecked ? 'checked' : ''; ?>
                                            data-role-key="<?= htmlspecialchars($role['key']); ?>"
                                            class="role-checkbox"
                                        >
                                        <span class="role-checkbox-label">
                                            <?= htmlspecialchars($role['name']); ?>
                                            <?php if ($isPrimary): ?>
                                                <span class="role-primary-badge">Primary</span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="primary_role" id="primary-role-input" value="<?= htmlspecialchars($primaryRoleKey); ?>">
                        </div>
                    </label>
                <?php else: ?>
                    <label>
                        <span>Role *</span>
                        <select name="role_key" required class="modern-select">
                            <option value="">Select a role</option>
                            <?php foreach ($assignableRoles as $role): ?>
                                <option value="<?= htmlspecialchars($role['key']); ?>" <?= $user['role_key'] === $role['key'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                            Note: Only Receptionist, Cashier, and Service Agent roles can have multiple role assignments.
                        </small>
                    </label>
                <?php endif; ?>
                
                <label>
                    <span>Status *</span>
                    <select name="status" required class="modern-select">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Update User
            </button>
            <a class="btn btn-outline" href="<?= base_url('staff/dashboard/staff'); ?>">Cancel</a>
        </div>
    </form>
</section>

<style>
.user-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.user-edit-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.user-edit-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.user-edit-form {
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

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
}

.form-field-full {
    grid-column: 1 / -1;
}

label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

label span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark);
}

.modern-input,
.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: #fff;
    transition: all 0.2s ease;
}

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.modern-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
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

.roles-checkbox-group {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

.roles-checkbox-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.role-checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.role-checkbox-item:hover {
    border-color: var(--primary);
    background: #f8fafc;
}

.role-checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary);
}

.role-checkbox-label {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: var(--dark);
    cursor: pointer;
}

.role-primary-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: var(--primary);
    color: #fff;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: auto;
}

@media (max-width: 768px) {
    .roles-checkbox-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.role-checkbox');
    const primaryInput = document.getElementById('primary-role-input');
    
    // Set primary role to first checked checkbox
    function updatePrimaryRole() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (checked.length > 0 && primaryInput) {
            // First checked becomes primary
            const firstChecked = checked[0];
            primaryInput.value = firstChecked.dataset.roleKey;
            
            // Update primary badge display
            checkboxes.forEach(cb => {
                const label = cb.closest('.role-checkbox-item').querySelector('.role-checkbox-label');
                const badge = label.querySelector('.role-primary-badge');
                if (cb === firstChecked) {
                    if (!badge) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'role-primary-badge';
                        newBadge.textContent = 'Primary';
                        label.appendChild(newBadge);
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        }
    }
    
    // Ensure at least one checkbox is checked
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const checked = Array.from(checkboxes).filter(c => c.checked);
            if (checked.length === 0) {
                // Prevent unchecking the last one
                this.checked = true;
                return;
            }
            updatePrimaryRole();
        });
    });
    
    // Initialize primary role on load
    updatePrimaryRole();
    
    // Debug: Log form submission
    const form = document.getElementById('staff-edit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const userIdField = document.getElementById('user_id_field');
            if (!userIdField || !userIdField.value) {
                console.error('User ID field is missing or empty!');
                e.preventDefault();
                alert('Error: User ID is missing. Cannot submit form.');
                return false;
            }
            console.log('Form submitting with user_id:', userIdField.value);
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

