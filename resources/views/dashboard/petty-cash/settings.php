<?php
$pageTitle = 'Petty Cash Settings | Hotela';
$account = $account ?? null;
$users = $users ?? [];
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <a href="<?= base_url('dashboard/petty-cash'); ?>" class="back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Petty Cash
            </a>
            <h2>Petty Cash Settings</h2>
            <p class="page-subtitle">Configure petty cash account settings</p>
        </div>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('dashboard/petty-cash/settings'); ?>" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">Account Information</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Account Name</span>
                    <input type="text" name="account_name" class="modern-input" value="<?= htmlspecialchars($account['account_name'] ?? 'Petty Cash'); ?>" placeholder="Petty Cash">
                </label>
                <label class="form-group required">
                    <span>Account Limit (KES)</span>
                    <input type="number" name="limit_amount" step="0.01" min="0" required class="modern-input" value="<?= htmlspecialchars($account['limit_amount'] ?? 2000); ?>" placeholder="2000.00">
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Maximum amount that can be held in petty cash</small>
                </label>
                <label class="form-group">
                    <span>Custodian</span>
                    <select name="custodian_id" class="modern-select">
                        <option value="">No Custodian</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id']; ?>" <?= ($account['custodian_id'] ?? null) == $user['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($user['name']); ?> (<?= htmlspecialchars($user['role_name'] ?? ''); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Person responsible for managing petty cash</small>
                </label>
                <label class="form-group">
                    <span>Status</span>
                    <select name="status" class="modern-select">
                        <option value="active" <?= ($account['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?= ($account['status'] ?? 'active') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="closed" <?= ($account['status'] ?? 'active') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= base_url('dashboard/petty-cash'); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
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

.settings-form {
    max-width: 800px;
}

.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #f1f5f9;
}

.form-section:last-of-type {
    border-bottom: none;
}

.section-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.required span::after {
    content: ' *';
    color: #dc2626;
}

.modern-input,
.modern-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

