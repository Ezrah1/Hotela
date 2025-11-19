<?php
$integrations = $settings['integrations'] ?? [];
?>

<fieldset>
    <legend>SMS</legend>
    <label>
        <span>SMS Gateway</span>
        <input type="text" name="sms_gateway" value="<?= htmlspecialchars($integrations['sms_gateway'] ?? ''); ?>">
    </label>
</fieldset>

<fieldset>
    <legend>Email (SMTP)</legend>
    <label>
        <span>SMTP Host</span>
        <input type="text" name="smtp_host" value="<?= htmlspecialchars($integrations['smtp_host'] ?? 'localhost'); ?>" placeholder="smtp.gmail.com">
    </label>
    <label>
        <span>SMTP Port</span>
        <input type="number" name="smtp_port" value="<?= htmlspecialchars($integrations['smtp_port'] ?? '587'); ?>" placeholder="587">
    </label>
    <label>
        <span>SMTP Username</span>
        <input type="text" name="smtp_username" value="<?= htmlspecialchars($integrations['smtp_username'] ?? ''); ?>" placeholder="your-email@gmail.com">
    </label>
    <label>
        <span>SMTP Password</span>
        <input type="password" name="smtp_password" value="<?= htmlspecialchars($integrations['smtp_password'] ?? ''); ?>" placeholder="Your SMTP password">
    </label>
    <label>
        <span>SMTP Encryption</span>
        <select name="smtp_encryption">
            <option value="tls" <?= ($integrations['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
            <option value="ssl" <?= ($integrations['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
            <option value="" <?= empty($integrations['smtp_encryption']) ? 'selected' : ''; ?>>None</option>
        </select>
    </label>
    <label class="checkbox">
        <input type="hidden" name="smtp_auth" value="0">
        <input type="checkbox" name="smtp_auth" value="1" <?= !empty($integrations['smtp_auth']) ? 'checked' : ''; ?>>
        <span>Enable SMTP Authentication</span>
    </label>
</fieldset>

