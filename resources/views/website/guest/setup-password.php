<?php
$website = settings('website', []);
$slot = function () use ($email, $code, $error) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Setup Password</h1>
            <p>Create a password for your guest account.</p>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-auth">
            <article class="card">
                <h2>Create Your Password</h2>
                
                <?php if ($error === 'missing'): ?>
                    <div class="alert error">Please enter all required fields.</div>
                <?php elseif ($error === 'invalid_code'): ?>
                    <div class="alert error">Invalid or expired code. Please request a new one.</div>
                <?php elseif ($error === 'password_mismatch'): ?>
                    <div class="alert error">Passwords do not match. Please try again.</div>
                <?php elseif ($error === 'password_short'): ?>
                    <div class="alert error">Password must be at least 8 characters long.</div>
                <?php elseif ($error === 'no_bookings'): ?>
                    <div class="alert error">No bookings found for this email address.</div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('guest/setup-password'); ?>" class="portal-form">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email); ?>">
                    <input type="hidden" name="code" value="<?= htmlspecialchars($code); ?>">
                    <label>
                        <span>Verification Code</span>
                        <input type="text" value="<?= htmlspecialchars($code); ?>" disabled style="text-align: center; font-size: 24px; letter-spacing: 8px; font-family: monospace; background: #f1f5f9;">
                        <small style="display: block; margin-top: 5px; color: #64748b;">Code from your email</small>
                    </label>
                    <label>
                        <span>Password</span>
                        <input type="password" name="password" placeholder="At least 8 characters" minlength="8" required>
                        <small style="display: block; margin-top: 5px; color: #64748b;">Must be at least 8 characters long</small>
                    </label>
                    <label>
                        <span>Confirm Password</span>
                        <input type="password" name="password_confirm" placeholder="Confirm your password" minlength="8" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Create Password</button>
                </form>
                
                <p style="margin-top: 20px; text-align: center;">
                    <a href="<?= base_url('guest/login'); ?>" style="color: #8b5cf6; text-decoration: none;">Back to login</a>
                </p>
            </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Setup Password | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

