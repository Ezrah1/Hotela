<?php
$pageTitle = 'Sign In | Hotela';
include base_path('resources/includes/header.php');
?>
<section class="login-page-wrapper">
    <div class="login-page-container">
        <div class="login-page-intro">
            <h1>Welcome to Hotela</h1>
            <p>Integrated Hospitality OS for modern hotels. Manage your property, staff, inventory, and guests from one powerful platform.</p>
            <div class="login-page-actions">
                <a href="<?= base_url('features'); ?>" class="btn btn-primary">View Features</a>
                <a href="<?= base_url('modules'); ?>" class="btn btn-outline">View Modules</a>
            </div>
        </div>
        <div class="auth-card login-card">
            <h2>Staff Sign In</h2>
            <p class="login-subtitle">Access your role-based Hotela workspace.</p>
            <?php if (!empty($error)): ?>
                <div class="alert danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['message'])): ?>
                <div class="alert info"><?= htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['logged_out'])): ?>
                <div class="alert success">You have been successfully logged out.</div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('staff/login'); ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '/staff/dashboard'); ?>">
                <label>
                    <span>Username</span>
                    <input type="text" name="username" required placeholder="first.last" autocomplete="username">
                    <small>Use your username (first.last format) or email to login</small>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Sign In</button>
            </form>
            <p class="login-help">
                <a href="<?= base_url('contact-developer'); ?>">Need help? Contact Developer</a>
            </p>
        </div>
    </div>
</section>

<?php include base_path('resources/includes/footer.php'); ?>

