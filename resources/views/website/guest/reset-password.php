<?php
$website = settings('website', []);
$slot = function () use ($email, $error, $success) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Reset Password</h1>
            <p>Enter the code sent to your email and your new password.</p>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-auth">
            <article class="card">
                <h2>Set New Password</h2>
                
                <?php if ($error === 'missing'): ?>
                    <div class="alert error">Please enter all required fields.</div>
                <?php elseif ($error === 'invalid_code'): ?>
                    <div class="alert error">Invalid or expired code. Please request a new one.</div>
                <?php elseif ($error === 'password_mismatch'): ?>
                    <div class="alert error">Passwords do not match. Please try again.</div>
                <?php elseif ($error === 'password_short'): ?>
                    <div class="alert error">Password must be at least 8 characters long.</div>
                <?php endif; ?>

                <?php if ($success === 'code_sent'): ?>
                    <div class="alert success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        A password reset code has been sent to <?= htmlspecialchars($email); ?>. Please check your email.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('guest/reset-password'); ?>" class="portal-form">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email); ?>">
                    <label>
                        <span>Verification Code</span>
                        <input type="text" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" style="text-align: center; font-size: 24px; letter-spacing: 8px; font-family: monospace;" required>
                        <small style="display: block; margin-top: 5px; color: #64748b;">Enter the 6-digit code from your email</small>
                    </label>
                    <label>
                        <span>New Password</span>
                        <input type="password" name="password" placeholder="At least 8 characters" minlength="8" required>
                        <small style="display: block; margin-top: 5px; color: #64748b;">Must be at least 8 characters long</small>
                    </label>
                    <label>
                        <span>Confirm Password</span>
                        <input type="password" name="password_confirm" placeholder="Confirm your password" minlength="8" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Reset Password</button>
                </form>
                
                <p style="margin-top: 20px; text-align: center;">
                    <a href="<?= base_url('guest/forgot-password'); ?>" style="color: #8b5cf6; text-decoration: none;">Request a new code</a> | 
                    <a href="<?= base_url('guest/login'); ?>" style="color: #8b5cf6; text-decoration: none;">Back to login</a>
                </p>
            </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Reset Password | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

