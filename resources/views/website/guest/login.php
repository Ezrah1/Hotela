<?php
$website = settings('website', []);
$slot = function () use ($redirect) {
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    $method = $_GET['method'] ?? 'password';
    $email = $_GET['email'] ?? '';
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Guest Portal</h1>
            <p>Access your bookings, dining orders, and concierge notes in one place.</p>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-auth">
        <article class="card">
            <h2>Sign in</h2>
            
            <!-- Login Method Tabs -->
            <div class="login-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0;">
                <button type="button" class="login-tab <?= $method === 'password' ? 'active' : ''; ?>" data-method="password" style="flex: 1; padding: 12px; background: none; border: none; cursor: pointer; border-bottom: 2px solid <?= $method === 'password' ? '#8b5cf6' : 'transparent'; ?>; color: <?= $method === 'password' ? '#8b5cf6' : '#64748b'; ?>; font-weight: <?= $method === 'password' ? 'bold' : 'normal'; ?>;">
                    With Password
                </button>
                <button type="button" class="login-tab <?= $method === 'code' ? 'active' : ''; ?>" data-method="code" style="flex: 1; padding: 12px; background: none; border: none; cursor: pointer; border-bottom: 2px solid <?= $method === 'code' ? '#8b5cf6' : 'transparent'; ?>; color: <?= $method === 'code' ? '#8b5cf6' : '#64748b'; ?>; font-weight: <?= $method === 'code' ? 'bold' : 'normal'; ?>;">
                    With Email Code
                </button>
            </div>

            <!-- Error Messages -->
            <?php if ($error === 'missing'): ?>
                <div class="alert error">Please enter all required fields.</div>
            <?php elseif ($error === 'invalid_credentials'): ?>
                <div class="alert error">Invalid email or password. Please try again.</div>
            <?php elseif ($error === 'invalid_code'): ?>
                <div class="alert error">Invalid or expired code. Please request a new code.</div>
            <?php elseif ($error === 'invalid_email'): ?>
                <div class="alert error">Please enter a valid email address.</div>
            <?php elseif ($error === 'no_bookings'): ?>
                <div class="alert error">No bookings found for this email address.</div>
            <?php elseif ($error === 'rate_limit'): ?>
                <div class="alert error">Please wait a few minutes before requesting another code.</div>
            <?php endif; ?>

            <!-- Success Messages -->
            <?php if ($success === 'code_sent'): ?>
                <div class="alert success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    A login code has been sent to <?= htmlspecialchars($email); ?>. Please check your email and enter the code below.
                </div>
            <?php elseif ($success === 'password_reset'): ?>
                <div class="alert success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    Your password has been reset successfully. You can now log in with your new password.
                </div>
            <?php endif; ?>

            <!-- Password Login Form -->
            <div id="login-password" class="login-method" style="display: <?= $method === 'password' ? 'block' : 'none'; ?>;">
                <p>Enter your email address and password to access your account.</p>
                <form method="post" action="<?= base_url('guest/login'); ?>" class="portal-form">
                    <input type="hidden" name="login_method" value="password">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                    <label>
                        <span>Email address</span>
                        <input type="email" name="email" placeholder="guest@example.com" value="<?= htmlspecialchars($email); ?>" required>
                    </label>
                    <label>
                        <span>Password</span>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Sign In</button>
                    <p style="margin-top: 15px; text-align: center;">
                        <a href="<?= base_url('guest/forgot-password'); ?>" style="color: #8b5cf6; text-decoration: none;">Forgot your password?</a>
                    </p>
                </form>
            </div>

            <!-- Email Code Login Form -->
            <div id="login-code" class="login-method" style="display: <?= $method === 'code' ? 'block' : 'none'; ?>;">
                <?php if ($success !== 'code_sent'): ?>
                    <p>Don't have your booking reference? Enter your email address and we'll send you a login code.</p>
                    <form method="post" action="<?= base_url('guest/login/request-code'); ?>" class="portal-form">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                        <label>
                            <span>Email address</span>
                            <input type="email" name="email" placeholder="guest@example.com" value="<?= htmlspecialchars($email); ?>" required>
                        </label>
                        <button class="btn btn-primary" type="submit">Send Login Code</button>
                    </form>
                <?php else: ?>
                    <p>Enter the 6-digit code sent to your email address.</p>
                    <form method="post" action="<?= base_url('guest/login'); ?>" class="portal-form">
                        <input type="hidden" name="login_method" value="code">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email); ?>">
                        <label>
                            <span>Login Code</span>
                            <input type="text" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" style="text-align: center; font-size: 24px; letter-spacing: 8px; font-family: monospace;" required>
                            <small style="display: block; margin-top: 5px; color: #64748b;">Enter the 6-digit code from your email</small>
                        </label>
                        <button class="btn btn-primary" type="submit">Verify & Access Portal</button>
                        <p style="margin-top: 15px; text-align: center;">
                            <a href="<?= base_url('guest/login?method=code&redirect=' . urlencode($redirect)); ?>" style="color: #8b5cf6; text-decoration: none;">Didn't receive the code? Request a new one</a>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </article>
        <article class="card card-outline">
            <h3>What you'll see</h3>
            <ul class="portal-benefits">
                <li>Upcoming & in-house bookings with live status.</li>
                <li>Food & drink orders with fulfillment updates.</li>
                <li>Concierge messages and special requests.</li>
                <li>Fast rebooking using saved preferences.</li>
            </ul>
        </article>
        </div>
    </section>
    <script>
        // Tab switching functionality
        document.querySelectorAll('.login-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const method = this.dataset.method;
                const redirect = new URLSearchParams(window.location.search).get('redirect') || '<?= base_url('guest/portal'); ?>';
                
                // Update active tab
                document.querySelectorAll('.login-tab').forEach(t => {
                    t.classList.remove('active');
                    t.style.borderBottomColor = 'transparent';
                    t.style.color = '#64748b';
                    t.style.fontWeight = 'normal';
                });
                this.classList.add('active');
                this.style.borderBottomColor = '#8b5cf6';
                this.style.color = '#8b5cf6';
                this.style.fontWeight = 'bold';
                
                // Show/hide forms
                document.querySelectorAll('.login-method').forEach(form => {
                    form.style.display = 'none';
                });
                document.getElementById('login-' + method).style.display = 'block';
                
                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('method', method);
                window.history.pushState({}, '', url);
            });
        });
    </script>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Guest Portal Login | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

