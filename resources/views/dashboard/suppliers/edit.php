<?php
$pageTitle = 'Edit Supplier | Hotela';
$supplier = $supplier ?? [];
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="page-header">
        <div>
            <a href="<?= base_url('staff/dashboard/suppliers'); ?>" class="back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Suppliers
            </a>
            <h2>Edit Supplier</h2>
            <p class="page-subtitle">Update supplier information</p>
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

    <form method="post" action="<?= base_url('staff/dashboard/suppliers/edit?id=' . $supplier['id']); ?>" class="supplier-form">
        <input type="hidden" name="id" value="<?= $supplier['id']; ?>">
        
        <div class="form-section">
            <h3 class="section-title">Basic Information</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Supplier Name</span>
                    <input type="text" name="name" required class="modern-input" value="<?= htmlspecialchars($supplier['name'] ?? ''); ?>" placeholder="Enter supplier name">
                </label>
                <label class="form-group">
                    <span>Contact Person</span>
                    <input type="text" name="contact_person" class="modern-input" value="<?= htmlspecialchars($supplier['contact_person'] ?? ''); ?>" placeholder="Contact person name">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Contact Details</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Email Address</span>
                    <input type="email" name="email" class="modern-input" value="<?= htmlspecialchars($supplier['email'] ?? ''); ?>" placeholder="supplier@example.com">
                </label>
                <label class="form-group">
                    <span>Phone Number</span>
                    <input type="tel" name="phone" class="modern-input" value="<?= htmlspecialchars($supplier['phone'] ?? ''); ?>" placeholder="+254 700 000 000">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Address</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Street Address</span>
                    <textarea name="address" class="modern-input" rows="3" placeholder="Street address"><?= htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
                </label>
                <label class="form-group">
                    <span>City</span>
                    <input type="text" name="city" class="modern-input" value="<?= htmlspecialchars($supplier['city'] ?? ''); ?>" placeholder="City">
                </label>
                <label class="form-group">
                    <span>Country</span>
                    <input type="text" name="country" class="modern-input" value="<?= htmlspecialchars($supplier['country'] ?? ''); ?>" placeholder="Country">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Business Details</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Tax ID / VAT Number</span>
                    <input type="text" name="tax_id" class="modern-input" value="<?= htmlspecialchars($supplier['tax_id'] ?? ''); ?>" placeholder="Tax identification number">
                </label>
                <label class="form-group">
                    <span>Payment Terms</span>
                    <input type="text" name="payment_terms" class="modern-input" value="<?= htmlspecialchars($supplier['payment_terms'] ?? ''); ?>" placeholder="e.g., Net 30, COD">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Payment Details</h3>
            <div class="form-grid">
                <label class="form-group">
                    <span>Bank Name</span>
                    <input type="text" name="bank_name" class="modern-input" value="<?= htmlspecialchars($supplier['bank_name'] ?? ''); ?>" placeholder="Bank name">
                </label>
                <label class="form-group">
                    <span>Account Number</span>
                    <input type="text" name="bank_account_number" class="modern-input" value="<?= htmlspecialchars($supplier['bank_account_number'] ?? ''); ?>" placeholder="Bank account number">
                </label>
                <label class="form-group">
                    <span>Branch</span>
                    <input type="text" name="bank_branch" class="modern-input" value="<?= htmlspecialchars($supplier['bank_branch'] ?? ''); ?>" placeholder="Bank branch">
                </label>
                <label class="form-group">
                    <span>SWIFT Code</span>
                    <input type="text" name="bank_swift_code" class="modern-input" value="<?= htmlspecialchars($supplier['bank_swift_code'] ?? ''); ?>" placeholder="SWIFT/BIC code">
                </label>
                <label class="form-group">
                    <span>Payment Methods</span>
                    <input type="text" name="payment_methods" class="modern-input" value="<?= htmlspecialchars($supplier['payment_methods'] ?? ''); ?>" placeholder="e.g., Bank Transfer, Cheque, M-Pesa">
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Comma-separated list of accepted payment methods</small>
                </label>
                <label class="form-group">
                    <span>Credit Limit (KES)</span>
                    <input type="number" name="credit_limit" step="0.01" min="0" class="modern-input" placeholder="0.00" value="<?= htmlspecialchars($supplier['credit_limit'] ?? '0'); ?>">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Classification</h3>
            <div class="form-grid">
                <label class="form-group required">
                    <span>Category</span>
                    <select name="category" class="modern-select" required>
                        <option value="product_supplier" <?= ($supplier['category'] ?? 'product_supplier') === 'product_supplier' ? 'selected' : ''; ?>>Product Supplier</option>
                        <option value="service_provider" <?= ($supplier['category'] ?? 'product_supplier') === 'service_provider' ? 'selected' : ''; ?>>Service Provider</option>
                        <option value="both" <?= ($supplier['category'] ?? 'product_supplier') === 'both' ? 'selected' : ''; ?>>Both (Products & Services)</option>
                    </select>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Service providers are for maintenance/repair work, product suppliers are for inventory items</small>
                </label>
                <label class="form-group">
                    <span>Supplier Group</span>
                    <input type="text" name="supplier_group" class="modern-input" value="<?= htmlspecialchars($supplier['supplier_group'] ?? ''); ?>" placeholder="e.g., Food & Beverage, Maintenance, Office Supplies" list="supplier-groups">
                    <datalist id="supplier-groups">
                        <?php if (!empty($groups)): ?>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= htmlspecialchars($group); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                    <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Optional: Group suppliers by type or department</small>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Additional Information</h3>
            <label class="form-group">
                <span>Notes</span>
                <textarea name="notes" class="modern-input" rows="4" placeholder="Additional notes about this supplier"><?= htmlspecialchars($supplier['notes'] ?? ''); ?></textarea>
            </label>
            <label class="form-group required">
                <span>Status</span>
                <select name="status" class="modern-select" required>
                    <option value="active" <?= ($supplier['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?= ($supplier['status'] ?? 'active') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="blacklisted" <?= ($supplier['status'] ?? 'active') === 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                    <option value="inactive" <?= ($supplier['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Suspended: Temporarily not accepting orders. Blacklisted: Permanently excluded from orders.</small>
            </label>
        </div>

        <div class="form-actions">
            <a href="<?= base_url('staff/dashboard/suppliers'); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Supplier</button>
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

.supplier-form {
    max-width: 900px;
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

.form-group span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark);
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

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

