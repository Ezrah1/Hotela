<?php
$pageTitle = 'System Settings';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>System Settings</h2>
        <p class="page-subtitle">Configure system-wide settings and preferences</p>
    </div>
</div>

<?php 
$twoFactorAuth = new \App\Services\TwoFactorAuth();
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
$setup2FA = isset($_GET['setup_2fa']);
$secret = $_SESSION['2fa_setup_secret'] ?? null;
$backupCodes = $_SESSION['2fa_backup_codes'] ?? null;

if ($backupCodes) {
    unset($_SESSION['2fa_backup_codes']);
}
?>

<?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom: 2rem;">
        <?php
        $errors = [
            'username_exists' => 'Username already exists. Please choose another.',
            'invalid_current_password' => 'Current password is incorrect.',
            'password_mismatch' => 'New passwords do not match.',
            'password_too_short' => 'Password must be at least 8 characters long.',
            'invalid_password' => 'Password is incorrect.',
            'invalid_2fa_code' => 'Invalid 2FA verification code.',
            '2fa_setup_failed' => '2FA setup failed. Please try again.',
        ];
        echo htmlspecialchars($errors[$error] ?? 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;">
        <?php
        $messages = [
            '1' => 'Settings updated successfully.',
            'username_updated' => 'Username updated successfully.',
            'password_updated' => 'Password updated successfully.',
            '2fa_enabled' => 'Two-factor authentication enabled successfully.',
            '2fa_disabled' => 'Two-factor authentication disabled successfully.',
        ];
        echo htmlspecialchars($messages[$success] ?? 'Settings updated successfully.');
        ?>
    </div>
<?php endif; ?>

<?php if ($backupCodes): ?>
    <div class="alert alert-warning" style="margin-bottom: 2rem;">
        <strong>⚠️ Save these backup codes!</strong>
        <p style="margin: 0.5rem 0;">If you lose access to your authenticator app, use these codes to log in. Store them securely.</p>
        <div style="background: #fff; padding: 1rem; border-radius: 0.5rem; margin-top: 0.5rem; font-family: monospace; font-size: 0.875rem;">
            <?php foreach ($backupCodes as $code): ?>
                <div><?= htmlspecialchars($code); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="sysadmin-card">
    <div class="card-header">
        <h3>General Settings</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('sysadmin/settings'); ?>">
            <div class="form-group">
                <label>
                    <span>System Name</span>
                    <input type="text" name="system_name" value="Hotela" class="modern-input">
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <span>Maintenance Mode</span>
                    <select name="maintenance_mode" class="modern-select">
                        <option value="0">Disabled</option>
                        <option value="1">Enabled</option>
                    </select>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Account Settings</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('sysadmin/settings'); ?>" style="margin-bottom: 2rem;">
            <input type="hidden" name="action" value="update_username">
            <div class="form-group">
                <label>
                    <span>Username</span>
                    <input type="text" name="username" value="<?= htmlspecialchars($admin['username'] ?? ''); ?>" class="modern-input" required>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Username</button>
            </div>
        </form>
        
        <form method="POST" action="<?= base_url('sysadmin/settings'); ?>">
            <input type="hidden" name="action" value="update_password">
            <div class="form-group">
                <label>
                    <span>Current Password</span>
                    <input type="password" name="current_password" class="modern-input" required>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>New Password</span>
                    <input type="password" name="new_password" class="modern-input" required minlength="8">
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Confirm New Password</span>
                    <input type="password" name="confirm_password" class="modern-input" required minlength="8">
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Two-Factor Authentication</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($admin['two_factor_enabled'])): ?>
            <div class="security-item">
                <div>
                    <strong>2FA Status</strong>
                    <p class="text-muted">Two-factor authentication is enabled for your account</p>
                </div>
                <span class="badge badge-success">Enabled</span>
            </div>
            
            <form method="POST" action="<?= base_url('sysadmin/settings'); ?>" style="margin-top: 1.5rem;" onsubmit="return confirm('Are you sure you want to disable 2FA? This will make your account less secure.');">
                <input type="hidden" name="action" value="disable_2fa">
                <div class="form-group">
                    <label>
                        <span>Enter your password to disable 2FA</span>
                        <input type="password" name="password" class="modern-input" required>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </div>
            </form>
        <?php else: ?>
            <div class="security-item">
                <div>
                    <strong>2FA Status</strong>
                    <p class="text-muted">Two-factor authentication is not enabled</p>
                </div>
                <span class="badge badge-warning">Disabled</span>
            </div>
            
            <?php if ($setup2FA && $secret): ?>
                <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 0.5rem;">
                    <h4 style="margin-bottom: 1rem;">Setup Two-Factor Authentication</h4>
                    <p style="margin-bottom: 1rem;">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):</p>
                    <div style="text-align: center; margin: 1.5rem 0;">
                        <img src="<?= htmlspecialchars($twoFactorAuth::getQRCodeUrl($secret, $admin['username'] . '@Hotela')); ?>" alt="2FA QR Code" style="border: 2px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; background: white;">
                    </div>
                    <p style="margin-bottom: 1rem; font-family: monospace; background: white; padding: 0.75rem; border-radius: 0.25rem; text-align: center;">
                        <strong>Secret Key:</strong> <?= htmlspecialchars($secret); ?>
                    </p>
                    <p style="margin-bottom: 1rem; font-size: 0.875rem; color: #64748b;">
                        After scanning, enter the 6-digit code from your app to verify:
                    </p>
                    <form method="POST" action="<?= base_url('sysadmin/settings'); ?>">
                        <input type="hidden" name="action" value="enable_2fa">
                        <input type="hidden" name="two_factor_secret" value="<?= htmlspecialchars($secret); ?>">
                        <div class="form-group">
                            <label>
                                <span>Verification Code</span>
                                <input type="text" name="verification_code" class="modern-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
                            </label>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Verify and Enable 2FA</button>
                            <a href="<?= base_url('sysadmin/settings'); ?>" class="btn btn-outline" style="margin-left: 0.5rem;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <form method="POST" action="<?= base_url('sysadmin/settings/setup-2fa'); ?>" style="margin-top: 1.5rem;">
                    <p style="margin-bottom: 1rem; color: #64748b;">
                        Two-factor authentication adds an extra layer of security to your account. 
                        You'll need to enter a code from your authenticator app in addition to your password when logging in.
                    </p>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enable 2FA</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Security Settings</h3>
    </div>
    <div class="card-body">
        <div class="security-item">
            <div>
                <strong>Audit Logging</strong>
                <p class="text-muted">All system admin actions are automatically logged</p>
            </div>
            <span class="badge badge-success">Enabled</span>
        </div>
        
        <div class="security-item" style="margin-top: 1rem;">
            <div>
                <strong>Session Timeout</strong>
                <p class="text-muted">System admin sessions expire after 8 hours of inactivity</p>
            </div>
            <span class="badge badge-info">8 hours</span>
        </div>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.modern-input, .modern-select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.modern-input:focus, .modern-select:focus {
    outline: none;
    border-color: #667eea;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.security-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.security-item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #1e293b;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #fecaca;
}

.alert-warning {
    background: #fffbeb;
    color: #92400e;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #fde68a;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.btn-danger {
    background: #dc2626;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-outline {
    background: white;
    color: #667eea;
    padding: 0.75rem 1.5rem;
    border: 2px solid #667eea;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

