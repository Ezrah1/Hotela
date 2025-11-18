<?php
$inventory = $settings['inventory'] ?? [];
?>
<label>
    <span>Low Stock Threshold</span>
    <input type="number" name="low_stock_threshold" value="<?= htmlspecialchars($inventory['low_stock_threshold'] ?? ''); ?>">
</label>
<label class="checkbox">
    <input type="hidden" name="auto_requisition" value="0">
    <input type="checkbox" name="auto_requisition" value="1" <?= !empty($inventory['auto_requisition']) ? 'checked' : ''; ?>>
    <span>Automatically create requisitions when below threshold</span>
</label>

