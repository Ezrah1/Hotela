<?php
$pageTitle = 'Add New Staff | Hotela';
ob_start();
?>
<section class="card">
    <header class="user-edit-header">
        <div>
            <h2>Add New Staff Member</h2>
            <p class="user-edit-subtitle">Create a new staff account</p>
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

    <form method="post" action="<?= base_url('staff/dashboard/staff/store'); ?>" class="user-edit-form">
        <div class="form-section">
            <h3 class="section-title">Basic Information</h3>
            <div class="form-grid">
                <label class="form-field-full">
                    <span>Full Name *</span>
                    <input type="text" name="name" required class="modern-input" placeholder="Enter full name">
                </label>
                <label>
                    <span>Email Address *</span>
                    <input type="email" name="email" required class="modern-input" placeholder="email@example.com">
                </label>
                <label>
                    <span>Username (Optional)</span>
                    <input type="text" name="username" class="modern-input" placeholder="Leave blank to use email">
                    <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                        If not provided, email will be used for login
                    </small>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Account Security</h3>
            <div class="form-grid">
                <label>
                    <span>Password *</span>
                    <input type="password" name="password" required class="modern-input" placeholder="Minimum 6 characters" minlength="6">
                    <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                        Minimum 6 characters
                    </small>
                </label>
                <label>
                    <span>Status *</span>
                    <select name="status" required class="modern-select">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Role & Permissions</h3>
            <div class="form-grid">
                <label class="form-field-full">
                    <span>Role *</span>
                    <select name="role_key" id="role-select" required class="modern-select">
                        <option value="">Select a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['key']); ?>">
                                <?= htmlspecialchars($role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                        Note: Only Receptionist, Cashier, and Service Agent roles can have multiple role assignments after creation.
                    </small>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Create Staff Member
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
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

