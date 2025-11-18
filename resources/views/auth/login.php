<?php
$pageTitle = 'Sign In | Hotela';
include base_path('resources/includes/header.php');
?>
<section class="auth-wrapper">
    <div class="auth-card">
        <h1>Staff Sign In</h1>
        <p>Access your role-based Hotela workspace.</p>
        <?php if (!empty($error)): ?>
            <div class="alert danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('login'); ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '/dashboard'); ?>">
            <label>
                <span>Email</span>
                <input type="email" name="email" required placeholder="you@hotel.com">
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <button class="btn btn-primary" type="submit">Sign In</button>
        </form>
    </div>
</section>
<?php include base_path('resources/includes/footer.php'); ?>

