<?php
$slot = function () use ($redirect) {
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    $method = $_GET['method'] ?? 'password';
    $email = $_GET['email'] ?? '';
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Supplier Portal</h1>
            <p>Access your purchase orders, invoices, and account information.</p>
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
                <div class="alert error">Invalid or expired code. Please request a new one.</div>
            <?php elseif ($error === 'account_not_found'): ?>
                <div class="alert error">Account not found. Please contact support.</div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Password Login Form -->
            <form method="post" action="<?= base_url('supplier/login'); ?>" id="password-form" style="display: <?= $method === 'password' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="login_method" value="password">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label for="email-password">Email Address</label>
                    <input type="email" id="email-password" name="email" value="<?= htmlspecialchars($email); ?>" required placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </form>

            <!-- Code Login Form -->
            <form method="post" action="<?= base_url('supplier/login'); ?>" id="code-form" style="display: <?= $method === 'code' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="login_method" value="code">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label for="email-code">Email Address</label>
                    <input type="email" id="email-code" name="email" value="<?= htmlspecialchars($email); ?>" required placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="code">Login Code</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="code" name="code" required placeholder="Enter 6-digit code" maxlength="6" style="flex: 1;">
                        <button type="button" id="request-code-btn" class="btn btn-outline" style="white-space: nowrap;">Request Code</button>
                    </div>
                    <small style="color: #64748b; margin-top: 5px; display: block;">We'll send a 6-digit code to your email</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In with Code</button>
            </form>
        </article>
        </div>
    </section>

    <script>
    // Tab switching
    document.querySelectorAll('.login-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const method = this.getAttribute('data-method');
            
            // Update tabs
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
            document.getElementById('password-form').style.display = method === 'password' ? 'block' : 'none';
            document.getElementById('code-form').style.display = method === 'code' ? 'block' : 'none';
        });
    });

    // Request code button
    document.getElementById('request-code-btn')?.addEventListener('click', function() {
        const email = document.getElementById('email-code').value;
        if (!email) {
            alert('Please enter your email address first');
            return;
        }

        const btn = this;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('<?= base_url('supplier/login/request-code'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Login code sent to your email!');
            } else {
                alert(data.message || 'Failed to send code. Please try again.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    });
    </script>

    <style>
    .portal-section {
        max-width: 500px;
        margin: 2rem auto;
    }
    .portal-auth .card {
        padding: 2rem;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #1e293b;
    }
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 1rem;
    }
    .form-group input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .alert.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    </style>
    <?php
    return ob_get_clean();
};
include view_path('layouts/public.php');
?>

