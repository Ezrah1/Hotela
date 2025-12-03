<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    // Use file modification time for cache busting - ensures fresh CSS on updates
    $cssPath = BASE_PATH . '/public/assets/css/main.css';
    $cssVersion = file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '?v=' . time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Hotela'; ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css' . $cssVersion); ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg'); ?>" type="image/svg+xml">
</head>
<body>
<header class="site-header">
    <div class="container">
        <?php
        $brandName = settings('branding.name', 'Hotela');
        $logoPath = settings('branding.logo', 'assets/img/hotela-logo.svg');
        $loggedIn = \App\Support\Auth::check();
        ?>
        <a class="brand" href="<?= base_url(); ?>" aria-label="<?= htmlspecialchars($brandName); ?>">
            <img src="<?= asset($logoPath); ?>" alt="<?= htmlspecialchars($brandName); ?> logo">
        </a>
        <nav class="nav">
            <a href="<?= base_url('features'); ?>">Features</a>
            <a href="<?= base_url('modules'); ?>">Modules</a>
            <a href="<?= base_url('contact-developer'); ?>">Contact Developer</a>
            <?php if ($loggedIn): ?>
                <a href="<?= base_url('staff/dashboard'); ?>">Dashboard</a>
                <a href="<?= base_url('staff/dashboard/pos'); ?>">POS</a>
                <a href="<?= base_url('staff/logout'); ?>">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('staff/login'); ?>">Staff Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main">

