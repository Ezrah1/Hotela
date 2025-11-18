<?php
$pageTitle = 'Add Funds to Petty Cash | Hotela';
$account = $account ?? null;
$error = $_GET['error'] ?? null;

$limitAmount = $account ? (float)($account['limit_amount'] ?? 2000) : 2000;
$currentBalance = $account ? (float)($account['balance'] ?? 0) : 0;
$maxDeposit = $limitAmount - $currentBalance;

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
            <h2>Add Funds to Petty Cash</h2>
            <p class="page-subtitle">Deposit money into the petty cash account</p>
        </div>
    </header>

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

    <div class="balance-info-box">
        <div class="info-item">
            <span class="info-label">Current Balance</span>
            <span class="info-value">KES <?= number_format($currentBalance, 2); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Account Limit</span>
            <span class="info-value">KES <?= number_format($limitAmount, 2); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Maximum Deposit</span>
            <span class="info-value info-highlight">KES <?= number_format($maxDeposit, 2); ?></span>
        </div>
    </div>

    <form method="post" action="<?= base_url('dashboard/petty-cash/deposit'); ?>" class="deposit-form">
        <div class="form-section">
            <h3 class="section-title">Deposit Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Amount (KES)</span>
                    <input type="number" name="amount" step="0.01" min="0.01" max="<?= $maxDeposit; ?>" required class="modern-input" placeholder="0.00">
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Maximum: KES <?= number_format($maxDeposit, 2); ?></small>
                </label>
                <label class="form-group required full-width">
                    <span>Description</span>
                    <textarea name="description" required class="modern-input" rows="3" placeholder="Describe the source of this deposit"></textarea>
                </label>
                <label class="form-group full-width">
                    <span>Notes</span>
                    <textarea name="notes" class="modern-input" rows="3" placeholder="Additional notes (optional)"></textarea>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= base_url('dashboard/petty-cash'); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-success">Add Funds</button>
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

.balance-info-box {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
}

.info-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark);
}

.info-value.info-highlight {
    color: var(--primary);
}

.deposit-form {
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

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group.required span::after {
    content: ' *';
    color: #dc2626;
}

.modern-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
    resize: vertical;
}

.modern-input:focus {
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

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .balance-info-box {
        grid-template-columns: 1fr;
    }

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

