<?php
$hotel = $settings['hotel'] ?? [];
?>
<label>
    <span>Default Check-in Time</span>
    <input type="time" name="check_in_time" value="<?= htmlspecialchars($hotel['check_in_time'] ?? ''); ?>">
</label>
<label>
    <span>Default Check-out Time</span>
    <input type="time" name="check_out_time" value="<?= htmlspecialchars($hotel['check_out_time'] ?? ''); ?>">
</label>
<label>
    <span>Default Room Type</span>
    <input type="text" name="default_room_type" value="<?= htmlspecialchars($hotel['default_room_type'] ?? ''); ?>">
</label>
<label>
    <span>Standard Room Rate (KES)</span>
    <input type="number" step="0.01" name="standard_rate" value="<?= htmlspecialchars($hotel['standard_rate'] ?? ''); ?>">
</label>

