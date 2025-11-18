<?php
$notifications = $settings['notifications'] ?? [];
?>
<label class="checkbox">
    <input type="hidden" name="email_enabled" value="0">
    <input type="checkbox" name="email_enabled" value="1" <?= !empty($notifications['email_enabled']) ? 'checked' : ''; ?>>
    <span>Enable email notifications</span>
</label>
<label class="checkbox">
    <input type="hidden" name="sms_enabled" value="0">
    <input type="checkbox" name="sms_enabled" value="1" <?= !empty($notifications['sms_enabled']) ? 'checked' : ''; ?>>
    <span>Enable SMS notifications</span>
</label>
<label>
    <span>Pre-arrival Reminder (hours before check-in)</span>
    <input type="number" name="pre_arrival_hours" value="<?= htmlspecialchars($notifications['pre_arrival_hours'] ?? ''); ?>">
</label>
<label>
    <span>From Email Address</span>
    <input type="email" name="default_from_email" value="<?= htmlspecialchars($notifications['default_from_email'] ?? 'noreply@hotela.local'); ?>" placeholder="noreply@yourdomain.com">
    <small>The email address that will appear as the sender</small>
</label>
<label>
    <span>From Name</span>
    <input type="text" name="default_from_name" value="<?= htmlspecialchars($notifications['default_from_name'] ?? 'Hotela'); ?>" placeholder="Hotela">
    <small>The name that will appear as the sender</small>
</label>

