<?php
$branding = $settings['branding'] ?? [];
?>
<label>
    <span>Platform Name</span>
    <input type="text" name="name" value="<?= htmlspecialchars($branding['name'] ?? ''); ?>" required>
</label>
<label>
    <span>Logo Path / URL</span>
    <input type="text" name="logo" value="<?= htmlspecialchars($branding['logo'] ?? ''); ?>" required>
</label>
<label>
    <span>Contact Email</span>
    <input type="email" name="contact_email" value="<?= htmlspecialchars($branding['contact_email'] ?? ''); ?>">
</label>
<label>
    <span>Contact Phone</span>
    <input type="text" name="contact_phone" value="<?= htmlspecialchars($branding['contact_phone'] ?? ''); ?>">
</label>

