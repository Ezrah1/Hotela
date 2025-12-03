<?php
$pageTitle = 'Create POS Item | Hotela';
ob_start();
?>
<section class="card">
    <header>
        <h2>Create POS Item</h2>
        <p class="muted">Add a new POS item and configure its recipe (BOM)</p>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/pos/items/store'); ?>" id="pos-item-form">
        <div class="form-grid">
            <div class="form-group">
                <label>
                    <span>Item Name *</span>
                    <input type="text" name="name" required placeholder="e.g., Coffee, Tea, Hot Water">
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>Category *</span>
                    <select name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>SKU</span>
                    <input type="text" name="sku" placeholder="Optional SKU">
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>Price (KES) *</span>
                    <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="tracked" value="0">
                    <input type="checkbox" name="tracked" value="1">
                    <span>Track inventory for this item</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="is_inventory_item" value="0">
                    <input type="checkbox" name="is_inventory_item" value="1">
                    <span>This is an inventory item (appears in inventory module)</span>
                    <small class="muted">Uncheck for non-inventory items like tea, plain coffee, hot water</small>
                </label>
            </div>
        </div>

        <div class="form-section" style="margin-top: 2rem;">
            <h3>Recipe / Bill of Materials (BOM)</h3>
            <p class="muted" style="margin-bottom: 1rem;">Define which inventory items (ingredients) are used to make this POS item and in what quantities.</p>
            
            <div id="components-list">
                <div class="component-row" data-index="0">
                    <div class="form-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr auto;">
                        <div class="form-group">
                            <label>
                                <span>Ingredient *</span>
                                <select name="components[0][inventory_item_id]" class="component-inventory-item" required>
                                    <option value="">Select ingredient</option>
                                    <?php foreach ($inventoryItems as $invItem): ?>
                                        <option value="<?= $invItem['id']; ?>" data-unit="<?= htmlspecialchars($invItem['unit'] ?? ''); ?>">
                                            <?= htmlspecialchars($invItem['name']); ?> (<?= htmlspecialchars($invItem['sku'] ?? ''); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <span>Quantity per Sale *</span>
                                <input type="number" name="components[0][quantity_per_sale]" step="0.001" min="0.001" required placeholder="1.0">
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <span>Unit</span>
                                <input type="text" name="components[0][source_unit]" class="component-unit" placeholder="e.g., cups, ml" readonly>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <span>Conversion Factor</span>
                                <input type="number" name="components[0][conversion_factor]" step="0.001" min="0.001" value="1.0" placeholder="1.0">
                                <small class="muted">For unit conversion (e.g., 1 cup = 250ml, factor = 0.25)</small>
                            </label>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="button" class="btn btn-outline btn-small remove-component" style="display: none;">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-outline" id="add-component" style="margin-top: 1rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Ingredient
            </button>
        </div>

        <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
            <button type="submit" class="btn btn-primary">Create Item</button>
            <a href="<?= base_url('staff/dashboard/pos/items'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<script>
let componentIndex = 1;

document.getElementById('add-component').addEventListener('click', function() {
    const template = document.querySelector('.component-row').cloneNode(true);
    template.setAttribute('data-index', componentIndex);
    
    // Update input names
    template.querySelectorAll('input, select').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[0\]/, '[' + componentIndex + ']');
        }
        if (input.value && input.type === 'number' && input.name.includes('quantity_per_sale')) {
            input.value = '';
        }
        if (input.value && input.type === 'number' && input.name.includes('conversion_factor')) {
            input.value = '1.0';
        }
    });
    
    // Show remove button
    template.querySelector('.remove-component').style.display = 'block';
    
    // Clear select
    template.querySelector('.component-inventory-item').value = '';
    template.querySelector('.component-unit').value = '';
    
    document.getElementById('components-list').appendChild(template);
    componentIndex++;
    
    // Attach event listeners
    attachComponentListeners(template);
});

function attachComponentListeners(row) {
    const select = row.querySelector('.component-inventory-item');
    const unitInput = row.querySelector('.component-unit');
    
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const unit = option.getAttribute('data-unit') || '';
        unitInput.value = unit;
    });
    
    const removeBtn = row.querySelector('.remove-component');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            row.remove();
        });
    }
}

// Attach listeners to initial row
document.querySelectorAll('.component-row').forEach(row => {
    attachComponentListeners(row);
});
</script>

<style>
.form-section {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.component-row {
    margin-bottom: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

