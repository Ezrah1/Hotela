<!DOCTYPE html>
<html lang="en">
<head>
    <?php $assetVersion = '?v=20251117-pos'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Hotela'; ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css' . $assetVersion); ?>">
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
            <a href="<?= base_url('/'); ?>">Home</a>
            <a href="<?= base_url('booking'); ?>">Booking</a>
            <a href="#modules">Modules</a>
            <a href="#contact">Contact</a>
            <?php if ($loggedIn): ?>
                <a href="<?= base_url('dashboard'); ?>">Dashboard</a>
                <a href="<?= base_url('dashboard/pos'); ?>">POS</a>
                <a href="<?= base_url('logout'); ?>">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('login'); ?>">Staff Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main">

