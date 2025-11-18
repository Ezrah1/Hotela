<?php
$pos = $settings['pos'] ?? [];
?>
<label>
    <span>Default Till</span>
    <input type="text" name="default_till" value="<?= htmlspecialchars($pos['default_till'] ?? ''); ?>">
</label>
<label>
    <span>Currency (ISO)</span>
    <input type="text" name="currency" value="<?= htmlspecialchars($pos['currency'] ?? ''); ?>">
</label>
<label>
    <span>Tax Rate (%)</span>
    <input type="number" step="0.01" name="tax_rate" value="<?= htmlspecialchars($pos['tax_rate'] ?? ''); ?>">
</label>
<label class="checkbox">
    <input type="hidden" name="enable_discounts" value="0">
    <input type="checkbox" name="enable_discounts" value="1" <?= !empty($pos['enable_discounts']) ? 'checked' : ''; ?>>
    <span>Enable discounts at POS</span>
</label>

