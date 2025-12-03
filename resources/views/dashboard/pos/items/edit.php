<?php
$pageTitle = 'Edit POS Item | Hotela';
ob_start();
?>
<section class="card">
    <header>
        <h2>Edit POS Item</h2>
        <p class="muted">Update POS item details and recipe (BOM)</p>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('staff/dashboard/pos/items/update'); ?>" id="pos-item-form">
        <input type="hidden" name="id" value="<?= $item['id']; ?>">
        
        <div class="form-grid">
            <div class="form-group">
                <label>
                    <span>Item Name *</span>
                    <input type="text" name="name" value="<?= htmlspecialchars($item['name']); ?>" required>
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>Category *</span>
                    <select name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" <?= $item['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>SKU</span>
                    <input type="text" name="sku" value="<?= htmlspecialchars($item['sku'] ?? ''); ?>" placeholder="Optional SKU">
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>Price (KES) *</span>
                    <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($item['price'] ?? 0); ?>" required>
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="tracked" value="0">
                    <input type="checkbox" name="tracked" value="1" <?= !empty($item['tracked']) ? 'checked' : ''; ?>>
                    <span>Track inventory for this item</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="is_inventory_item" value="0">
                    <input type="checkbox" name="is_inventory_item" value="1" <?= !empty($item['is_inventory_item']) ? 'checked' : ''; ?>>
                    <span>This is an inventory item (appears in inventory module)</span>
                    <small class="muted">Uncheck for non-inventory items like tea, plain coffee, hot water</small>
                </label>
            </div>
        </div>

        <?php if ((float)($item['production_cost'] ?? 0) > 0): ?>
            <div class="info-banner" style="margin: 1.5rem 0; padding: 1rem; background: #dbeafe; border-radius: 0.5rem; color: #1e40af;">
                <strong>Production Cost:</strong> KES <?= number_format((float)$item['production_cost'], 2); ?>
                <small style="display: block; margin-top: 0.25rem; opacity: 0.8;">Calculated from ingredient costs</small>
            </div>
        <?php endif; ?>

        <div class="form-section" style="margin-top: 2rem;">
            <h3>Recipe / Bill of Materials (BOM)</h3>
            <p class="muted" style="margin-bottom: 1rem;">Define which inventory items (ingredients) are used to make this POS item and in what quantities.</p>
            
            <div id="components-list">
                <?php if (empty($components)): ?>
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
                                </label>
                            </div>
                            
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="button" class="btn btn-outline btn-small remove-component" style="display: none;">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($components as $index => $component): ?>
                        <div class="component-row" data-index="<?= $index; ?>">
                            <div class="form-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr auto;">
                                <div class="form-group">
                                    <label>
                                        <span>Ingredient *</span>
                                        <select name="components[<?= $index; ?>][inventory_item_id]" class="component-inventory-item" required>
                                            <option value="">Select ingredient</option>
                                            <?php foreach ($inventoryItems as $invItem): ?>
                                                <option value="<?= $invItem['id']; ?>" 
                                                        data-unit="<?= htmlspecialchars($invItem['unit'] ?? ''); ?>"
                                                        <?= $component['inventory_item_id'] == $invItem['id'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($invItem['name']); ?> (<?= htmlspecialchars($invItem['sku'] ?? ''); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <span>Quantity per Sale *</span>
                                        <input type="number" name="components[<?= $index; ?>][quantity_per_sale]" 
                                               step="0.001" min="0.001" 
                                               value="<?= htmlspecialchars($component['quantity_per_sale'] ?? 1); ?>" 
                                               required>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <span>Unit</span>
                                        <input type="text" name="components[<?= $index; ?>][source_unit]" 
                                               class="component-unit" 
                                               value="<?= htmlspecialchars($component['source_unit'] ?? $component['inventory_item_unit'] ?? ''); ?>" 
                                               placeholder="e.g., cups, ml" readonly>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <span>Conversion Factor</span>
                                        <input type="number" name="components[<?= $index; ?>][conversion_factor]" 
                                               step="0.001" min="0.001" 
                                               value="<?= htmlspecialchars($component['conversion_factor'] ?? 1.0); ?>" 
                                               placeholder="1.0">
                                    </label>
                                </div>
                                
                                <div class="form-group" style="display: flex; align-items: flex-end;">
                                    <button type="button" class="btn btn-outline btn-small remove-component">Remove</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            <button type="submit" class="btn btn-primary">Update Item</button>
            <a href="<?= base_url('staff/dashboard/pos/items'); ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>

<script>
let componentIndex = <?= count($components ?? []); ?>;

document.getElementById('add-component').addEventListener('click', function() {
    const template = document.querySelector('.component-row').cloneNode(true);
    template.setAttribute('data-index', componentIndex);
    
    // Update input names
    template.querySelectorAll('input, select').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[\d+\]/, '[' + componentIndex + ']');
        }
        if (input.value && input.type === 'number' && input.name.includes('quantity_per_sale')) {
            input.value = '';
        }
        if (input.value && input.type === 'number' && input.name.includes('conversion_factor')) {
            input.value = '1.0';
        }
    });
    
    // Clear select
    template.querySelector('.component-inventory-item').value = '';
    template.querySelector('.component-unit').value = '';
    
    // Show remove button
    template.querySelector('.remove-component').style.display = 'block';
    
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

// Attach listeners to existing rows
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

.info-banner {
    margin: 1.5rem 0;
    padding: 1rem;
    background: #dbeafe;
    border-radius: 0.5rem;
    color: #1e40af;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

