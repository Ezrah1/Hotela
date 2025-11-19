<?php
$pageTitle = 'Sign In | Hotela';
include base_path('resources/includes/header.php');
?>
<section class="auth-wrapper" style="padding: 80px 20px; background: #f8f9fa; min-height: 70vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
        <div>
            <h1 style="font-size: 3em; color: #0d9488; margin-bottom: 20px;">Welcome to Hotela</h1>
            <p style="font-size: 1.3em; color: #666; margin-bottom: 30px; line-height: 1.6;">Integrated Hospitality OS for modern hotels. Manage your property, staff, inventory, and guests from one powerful platform.</p>
            <div style="margin-top: 40px;">
                <a href="<?= base_url('features'); ?>" style="display: inline-block; padding: 12px 30px; background: #0d9488; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; margin-right: 15px;">View Features</a>
                <a href="<?= base_url('modules'); ?>" style="display: inline-block; padding: 12px 30px; background: transparent; color: #0d9488; text-decoration: none; border-radius: 5px; font-weight: 600; border: 2px solid #0d9488;">View Modules</a>
            </div>
        </div>
        <div class="auth-card" style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 10px; color: #0d9488; font-size: 2em;">Staff Sign In</h2>
            <p style="text-align: center; margin-bottom: 30px; color: #666;">Access your role-based Hotela workspace.</p>
            <?php if (!empty($error)): ?>
                <div class="alert danger" style="background: #fee; color: #c33; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['message'])): ?>
                <div class="alert info" style="background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert danger" style="background: #fee; color: #c33; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?= htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('staff/login'); ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '/staff/dashboard'); ?>">
                <label style="display: block; margin-bottom: 20px;">
                    <span style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Email</span>
                    <input type="email" name="email" required placeholder="you@hotel.com" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box;">
                </label>
                <label style="display: block; margin-bottom: 20px;">
                    <span style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Password</span>
                    <input type="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box;">
                </label>
                <button class="btn btn-primary" type="submit" style="width: 100%; padding: 12px; background: #0d9488; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer;">Sign In</button>
            </form>
            <p style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9em;">
                <a href="<?= base_url('contact-developer'); ?>" style="color: #0d9488; text-decoration: none;">Need help? Contact Developer</a>
            </p>
        </div>
    </div>
</section>

<?php include base_path('resources/includes/footer.php'); ?>

