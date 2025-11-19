<?php
$pageTitle = 'Add New Expense | Hotela';
$categories = $categories ?? [];
$suppliers = $suppliers ?? [];
$error = $_GET['error'] ?? null;

$departments = [
    'operations' => 'Operations',
    'finance' => 'Finance',
    'kitchen' => 'Kitchen',
    'housekeeping' => 'Housekeeping',
    'maintenance' => 'Maintenance',
    'security' => 'Security',
    'front_desk' => 'Front Desk',
    'management' => 'Management',
];

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <a href="<?= base_url('staff/dashboard/expenses'); ?>" class="back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Expenses
            </a>
            <h2>Add New Expense</h2>
            <p class="page-subtitle">Record a new business expense or bill</p>
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

    <form method="post" action="<?= base_url('staff/dashboard/expenses/create'); ?>" class="expense-form">
        <div class="form-section">
            <h3 class="section-title">Expense Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Description</span>
                    <textarea name="description" required class="modern-input" rows="3" placeholder="Describe the expense"></textarea>
                </label>
                <label class="form-group required">
                    <span>Amount (KES)</span>
                    <input type="number" name="amount" step="0.01" min="0" required class="modern-input" placeholder="0.00">
                </label>
                <label class="form-group required">
                    <span>Expense Date</span>
                    <input type="date" name="expense_date" required class="modern-input" value="<?= date('Y-m-d'); ?>">
                </label>
                <label class="form-group">
                    <span>Department</span>
                    <select name="department" class="modern-select">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key); ?>"><?= htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-group">
                    <span>Category</span>
                    <select name="category_id" id="category-select" class="modern-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" data-petty-cash="<?= !empty($category['is_petty_cash']) ? '1' : '0'; ?>">
                                <?= htmlspecialchars($category['name']); ?>
                                <?php if (!empty($category['is_petty_cash'])): ?>
                                    (Petty Cash)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Optional: Categorize this expense. Categories marked "Petty Cash" will automatically deduct from petty cash.</small>
                    <div id="petty-cash-warning" style="display: none; margin-top: 0.5rem; padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem; border: 1px solid #f59e0b;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #92400e;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span style="font-size: 0.875rem; font-weight: 500;">This expense will be paid from petty cash and will reduce the petty cash balance.</span>
                        </div>
                    </div>
                </label>
                <label class="form-group">
                    <span>Payment Method</span>
                    <select name="payment_method" class="modern-select">
                        <option value="bank_transfer" selected>Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="card">Card</option>
                        <option value="other">Other</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Supplier Information (Optional)</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Supplier</span>
                    <select name="supplier_id" id="supplier-select" class="modern-select">
                        <option value="">No Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id']; ?>" data-balance="<?= htmlspecialchars($supplier['current_balance'] ?? 0); ?>" data-limit="<?= htmlspecialchars($supplier['credit_limit'] ?? 0); ?>">
                                <?= htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Optional: Link this expense to a supplier. For supplier bills, use <a href="<?= base_url('staff/dashboard/bills/create'); ?>" style="color: var(--primary);">Bills Management</a> instead.</small>
                </label>
                <div class="form-group full-width">
                    <div id="supplier-info" style="display: none; padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; margin-top: 0.5rem;">
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            <div>
                                <span style="font-size: 0.875rem; color: #64748b;">Current Balance:</span>
                                <span id="supplier-balance" style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;">KES 0.00</span>
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; color: #64748b;">Credit Limit:</span>
                                <span id="supplier-limit" style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;">KES 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Recurring Expense</h3>
            <label class="form-group checkbox-group">
                <input type="checkbox" name="is_recurring" id="is_recurring" value="1">
                <span>This is a recurring expense</span>
            </label>
            <div id="recurring-options" style="display: none; margin-top: 1rem;">
                <label class="form-group">
                    <span>Frequency</span>
                    <select name="recurring_frequency" class="modern-select">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Status & Notes</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Status</span>
                    <select name="status" class="modern-select">
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                    </select>
                </label>
                <label class="form-group full-width">
                    <span>Notes</span>
                    <textarea name="notes" class="modern-input" rows="4" placeholder="Additional notes about this expense"></textarea>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= base_url('staff/dashboard/expenses'); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Expense</button>
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

.expense-form {
    max-width: 1000px;
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

.checkbox-group {
    flex-direction: row;
    align-items: center;
    gap: 0.75rem;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
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
    resize: vertical;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierSelect = document.getElementById('supplier-select');
    const supplierInfo = document.getElementById('supplier-info');
    const supplierBalance = document.getElementById('supplier-balance');
    const supplierLimit = document.getElementById('supplier-limit');
    const isRecurring = document.getElementById('is_recurring');
    const recurringOptions = document.getElementById('recurring-options');

    if (supplierSelect) {
        supplierSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const balance = parseFloat(selectedOption.dataset.balance || 0);
                const limit = parseFloat(selectedOption.dataset.limit || 0);
                supplierBalance.textContent = 'KES ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                supplierLimit.textContent = 'KES ' + limit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                supplierInfo.style.display = 'block';
            } else {
                supplierInfo.style.display = 'none';
            }
        });
    }

    if (isRecurring) {
        isRecurring.addEventListener('change', function() {
            recurringOptions.style.display = this.checked ? 'block' : 'none';
        });
    }

    const categorySelect = document.getElementById('category-select');
    const pettyCashWarning = document.getElementById('petty-cash-warning');

    if (categorySelect && pettyCashWarning) {
        categorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const isPettyCash = selectedOption.dataset.pettyCash === '1';
            pettyCashWarning.style.display = isPettyCash ? 'block' : 'none';
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

