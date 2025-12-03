<?php
$website = settings('website', []);
$slot = function () use ($error, $success) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Forgot Password</h1>
            <p>Enter your email address to receive a password reset code.</p>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-auth">
            <article class="card">
                <h2>Reset Your Password</h2>
                
                <?php if ($error === 'invalid_email'): ?>
                    <div class="alert error">Please enter a valid email address.</div>
                <?php elseif ($error === 'rate_limit'): ?>
                    <div class="alert error">Please wait a few minutes before requesting another code.</div>
                <?php endif; ?>

                <?php if ($success === 'reset_sent'): ?>
                    <div class="alert success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        If an account exists with that email, a password reset code has been sent. Please check your email.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('guest/forgot-password'); ?>" class="portal-form">
                    <label>
                        <span>Email address</span>
                        <input type="email" name="email" placeholder="guest@example.com" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Send Reset Code</button>
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

$pageTitle = 'Forgot Password | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

