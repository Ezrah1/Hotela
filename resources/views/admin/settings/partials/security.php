<?php
$security = $settings['security'] ?? [];
?>
<label>
    <span>Session Timeout (minutes)</span>
    <input type="number" name="session_timeout" value="<?= htmlspecialchars($security['session_timeout'] ?? ''); ?>">
</label>
<label class="checkbox">
    <input type="hidden" name="two_factor" value="0">
    <input type="checkbox" name="two_factor" value="1" <?= !empty($security['two_factor']) ? 'checked' : ''; ?>>
    <span>Require 2FA for admin logins</span>
</label>
<label class="checkbox">
    <input type="hidden" name="audit_trail" value="0">
    <input type="checkbox" name="audit_trail" value="1" <?= !empty($security['audit_trail']) ? 'checked' : ''; ?>>
    <span>Enforce audit logging on critical updates</span>
</label>

