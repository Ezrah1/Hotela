<?php
$staff = $settings['staff'] ?? [];
$payslip = $settings['payslip'] ?? [];
?>
<h4>Staff Management</h4>
<label class="checkbox">
    <input type="hidden" name="roles_manageable" value="0">
    <input type="checkbox" name="roles_manageable" value="1" <?= !empty($staff['roles_manageable']) ? 'checked' : ''; ?>>
    <span>Allow admins to edit roles & permissions from portal</span>
</label>
<label>
    <span>Default Role for new staff</span>
    <input type="text" name="default_role" value="<?= htmlspecialchars($staff['default_role'] ?? ''); ?>">
</label>

<h4>Payslip Settings</h4>
<label class="checkbox">
    <input type="hidden" name="payslip[enabled]" value="0">
    <input type="checkbox" name="payslip[enabled]" value="1" <?= !empty($payslip['enabled']) ? 'checked' : ''; ?>>
    <span>Enable Payslip Feature</span>
</label>
<small style="display: block; margin-top: 0.5rem; color: #64748b;">When enabled, staff members can view their payslips from their dashboard.</small>

