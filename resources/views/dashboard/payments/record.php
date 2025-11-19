<?php
$pageTitle = 'Record Payment | Hotela';
$paymentType = $paymentType ?? 'expense';
$expense = $expense ?? null;
$supplier = $supplier ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <a href="<?= base_url('staff/dashboard/payments'); ?>" class="back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Payments
            </a>
            <h2>Record Payment</h2>
            <p class="page-subtitle">Record a payment for expense, bill, or supplier</p>
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

    <form method="post" action="<?= base_url('staff/dashboard/payments/record'); ?>" class="payment-form">
        <div class="form-section">
            <h3 class="section-title">Payment Type</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Payment For</span>
                    <select name="payment_type" id="payment-type-select" class="modern-select" required>
                        <option value="expense" <?= $paymentType === 'expense' ? 'selected' : ''; ?>>Expense</option>
                        <option value="bill" <?= $paymentType === 'bill' ? 'selected' : ''; ?>>Bill</option>
                        <option value="supplier" <?= $paymentType === 'supplier' ? 'selected' : ''; ?>>Supplier Payment</option>
                        <option value="other" <?= $paymentType === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </label>
            </div>
        </div>

        <!-- Expense Selection -->
        <div class="form-section" id="expense-section" style="display: <?= $paymentType === 'expense' ? 'block' : 'none'; ?>;">
            <h3 class="section-title">Expense Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Expense ID</span>
                    <input type="number" name="expense_id" id="expense-id" class="modern-input" placeholder="Enter expense ID" value="<?= $expense ? $expense['id'] : ''; ?>" <?= $paymentType === 'expense' ? 'required' : ''; ?>>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Enter the expense ID to pay for</small>
                </label>
                <?php if ($expense): ?>
                    <div class="form-group full-width">
                        <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Expense Reference:</span>
                                    <span style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;"><?= htmlspecialchars($expense['reference']); ?></span>
                                </div>
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Amount:</span>
                                    <span style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;">KES <?= number_format((float)$expense['amount'], 2); ?></span>
                                </div>
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Status:</span>
                                    <span style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;"><?= htmlspecialchars(ucfirst($expense['status'])); ?></span>
                                </div>
                            </div>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;">
                                <span style="font-size: 0.875rem; color: #64748b;">Description:</span>
                                <p style="margin: 0.25rem 0 0 0; color: var(--dark);"><?= htmlspecialchars($expense['description']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bill Selection -->
        <div class="form-section" id="bill-section" style="display: <?= $paymentType === 'bill' ? 'block' : 'none'; ?>;">
            <h3 class="section-title">Bill Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Bill ID</span>
                    <input type="number" name="bill_id" id="bill-id" class="modern-input" placeholder="Enter bill ID" <?= $paymentType === 'bill' ? 'required' : ''; ?>>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Enter the bill ID to pay for</small>
                </label>
            </div>
        </div>

        <!-- Supplier Selection -->
        <div class="form-section" id="supplier-section" style="display: <?= $paymentType === 'supplier' ? 'block' : 'none'; ?>;">
            <h3 class="section-title">Supplier Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Supplier ID</span>
                    <input type="number" name="supplier_id" id="supplier-id" class="modern-input" placeholder="Enter supplier ID" value="<?= $supplier ? $supplier['id'] : ''; ?>" <?= $paymentType === 'supplier' ? 'required' : ''; ?>>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Enter the supplier ID to pay</small>
                </label>
                <?php if ($supplier): ?>
                    <div class="form-group full-width">
                        <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Supplier:</span>
                                    <span style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;"><?= htmlspecialchars($supplier['name']); ?></span>
                                </div>
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Current Balance:</span>
                                    <span style="font-weight: 600; color: <?= (float)($supplier['current_balance'] ?? 0) > 0 ? '#dc2626' : '#16a34a'; ?>; margin-left: 0.5rem;">
                                        KES <?= number_format((float)($supplier['current_balance'] ?? 0), 2); ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size: 0.875rem; color: #64748b;">Credit Limit:</span>
                                    <span style="font-weight: 600; color: var(--dark); margin-left: 0.5rem;">KES <?= number_format((float)($supplier['credit_limit'] ?? 0), 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Payment Details</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Amount (KES)</span>
                    <input type="number" name="amount" step="0.01" min="0" required class="modern-input" placeholder="0.00" value="<?= $expense ? $expense['amount'] : ''; ?>">
                </label>
                <label class="form-group required">
                    <span>Payment Date</span>
                    <input type="date" name="payment_date" required class="modern-input" value="<?= date('Y-m-d'); ?>">
                </label>
                <label class="form-group required">
                    <span>Payment Method</span>
                    <select name="payment_method" id="payment-method-select" class="modern-select" required>
                        <option value="bank_transfer" selected>Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="card">Card</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="form-group" id="transaction-reference-group">
                    <span>Transaction Reference</span>
                    <input type="text" name="transaction_reference" class="modern-input" placeholder="Enter transaction reference">
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Optional: Reference number for bank transfer, M-Pesa, etc.</small>
                </label>
                <label class="form-group" id="cheque-number-group" style="display: none;">
                    <span>Cheque Number</span>
                    <input type="text" name="cheque_number" class="modern-input" placeholder="Enter cheque number">
                </label>
                <label class="form-group" id="bank-details-group" style="display: none;">
                    <span>Bank Name</span>
                    <input type="text" name="bank_name" class="modern-input" placeholder="Enter bank name">
                </label>
                <label class="form-group" id="account-number-group" style="display: none;">
                    <span>Account Number</span>
                    <input type="text" name="account_number" class="modern-input" placeholder="Enter account number">
                </label>
                <label class="form-group full-width">
                    <span>Notes</span>
                    <textarea name="notes" class="modern-input" rows="3" placeholder="Additional notes about this payment"></textarea>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= base_url('staff/dashboard/payments'); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Record Payment</button>
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

.payment-form {
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
    const paymentTypeSelect = document.getElementById('payment-type-select');
    const expenseSection = document.getElementById('expense-section');
    const billSection = document.getElementById('bill-section');
    const supplierSection = document.getElementById('supplier-section');
    const expenseIdInput = document.getElementById('expense-id');
    const billIdInput = document.getElementById('bill-id');
    const supplierIdInput = document.getElementById('supplier-id');
    const paymentMethodSelect = document.getElementById('payment-method-select');
    const transactionReferenceGroup = document.getElementById('transaction-reference-group');
    const chequeNumberGroup = document.getElementById('cheque-number-group');
    const bankDetailsGroup = document.getElementById('bank-details-group');
    const accountNumberGroup = document.getElementById('account-number-group');

    // Handle payment type change
    if (paymentTypeSelect) {
        paymentTypeSelect.addEventListener('change', function() {
            const type = this.value;
            expenseSection.style.display = type === 'expense' ? 'block' : 'none';
            billSection.style.display = type === 'bill' ? 'block' : 'none';
            supplierSection.style.display = type === 'supplier' ? 'block' : 'none';
            
            // Update required attributes
            if (expenseIdInput) {
                expenseIdInput.required = type === 'expense';
            }
            if (billIdInput) {
                billIdInput.required = type === 'bill';
            }
            if (supplierIdInput) {
                supplierIdInput.required = type === 'supplier';
            }
        });
    }

    // Handle payment method change
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            const method = this.value;
            transactionReferenceGroup.style.display = (method === 'bank_transfer' || method === 'mpesa' || method === 'card') ? 'block' : 'none';
            chequeNumberGroup.style.display = method === 'cheque' ? 'block' : 'none';
            bankDetailsGroup.style.display = method === 'bank_transfer' ? 'block' : 'none';
            accountNumberGroup.style.display = method === 'bank_transfer' ? 'block' : 'none';
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

