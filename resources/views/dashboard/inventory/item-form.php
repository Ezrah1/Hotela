<?php
$pageTitle = ($mode === 'edit' ? 'Edit' : 'Add') . ' Inventory Item | Hotela';
$item = $item ?? null;
$categories = $categories ?? [];
ob_start();
?>

<section class="card">
    <header class="form-header">
        <div>
            <h2><?= $mode === 'edit' ? 'Edit' : 'Add'; ?> Inventory Item</h2>
            <p class="form-subtitle"><?= $mode === 'edit' ? 'Update inventory item details' : 'Create a new inventory item'; ?></p>
        </div>
        <a href="<?= base_url('staff/dashboard/inventory'); ?>" class="btn btn-outline">
            ← Back to Inventory
        </a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= $mode === 'edit' ? base_url('staff/dashboard/inventory/item/update') : base_url('staff/dashboard/inventory/item/store'); ?>" class="item-form">
        <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>
                    <span>Item Name <span class="required">*</span></span>
                    <input type="text" name="name" value="<?= htmlspecialchars($item['name'] ?? ''); ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>SKU</span>
                    <input type="text" name="sku" value="<?= htmlspecialchars($item['sku'] ?? ''); ?>" placeholder="Auto-generated if left empty">
                    <small>Stock Keeping Unit - unique identifier</small>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Unit</span>
                    <select name="unit">
                        <option value="unit" <?= ($item['unit'] ?? 'unit') === 'unit' ? 'selected' : ''; ?>>Unit</option>
                        <option value="kg" <?= ($item['unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="g" <?= ($item['unit'] ?? '') === 'g' ? 'selected' : ''; ?>>Gram (g)</option>
                        <option value="L" <?= ($item['unit'] ?? '') === 'L' ? 'selected' : ''; ?>>Liter (L)</option>
                        <option value="mL" <?= ($item['unit'] ?? '') === 'mL' ? 'selected' : ''; ?>>Milliliter (mL)</option>
                        <option value="piece" <?= ($item['unit'] ?? '') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                        <option value="box" <?= ($item['unit'] ?? '') === 'box' ? 'selected' : ''; ?>>Box</option>
                        <option value="pack" <?= ($item['unit'] ?? '') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                        <option value="bottle" <?= ($item['unit'] ?? '') === 'bottle' ? 'selected' : ''; ?>>Bottle</option>
                        <option value="can" <?= ($item['unit'] ?? '') === 'can' ? 'selected' : ''; ?>>Can</option>
                    </select>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Category</span>
                    <input type="text" name="category" value="<?= htmlspecialchars($item['category'] ?? ''); ?>" list="categories" placeholder="Enter or select category">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Reorder Point</span>
                    <input type="number" name="reorder_point" value="<?= htmlspecialchars($item['reorder_point'] ?? '0'); ?>" step="0.01" min="0" placeholder="0">
                    <small>Minimum stock level before reordering</small>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Average Cost (KES)</span>
                    <input type="number" name="avg_cost" value="<?= htmlspecialchars($item['avg_cost'] ?? '0'); ?>" step="0.01" min="0" placeholder="0.00">
                    <small>Average purchase cost per unit</small>
                </label>
            </div>

            <div class="form-group">
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_pos_item" value="1" <?= !empty($item['is_pos_item']) ? 'checked' : ''; ?>>
                <span>Available in POS</span>
                <small>Allow this item to be sold through the POS system</small>
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="allow_negative" value="1" <?= !empty($item['allow_negative']) ? 'checked' : ''; ?>>
                <span>Allow Negative Stock</span>
                <small>Allow stock to go below zero (useful for items sold before receiving stock)</small>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $mode === 'edit' ? 'Update Item' : 'Create Item'; ?>
            </button>
            <a href="<?= base_url('staff/dashboard/inventory'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<style>
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.form-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.form-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.item-form {
    max-width: 800px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label span {
    font-weight: 500;
    color: var(--dark);
    font-size: 0.95rem;
}

.required {
    color: #ef4444;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.form-group small {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: -0.25rem;
}

.checkbox-label {
    flex-direction: row !important;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin-top: 0.25rem;
    cursor: pointer;
}

.checkbox-label span {
    font-weight: 500;
    color: var(--dark);
}

.checkbox-label small {
    display: block;
    margin-top: 0.25rem;
    font-weight: normal;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #8a6a3f;
}

.btn-outline {
    background: white;
    color: var(--dark);
    border: 1px solid #e2e8f0;
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.alert {
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

@media (max-width: 768px) {
    .form-header {
        flex-direction: column;
        gap: 1rem;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

