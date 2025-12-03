<?php
$brandName = settings('branding.name', 'Hotela');
$logoPath = settings('branding.logo', 'assets/img/hotela-logo.svg');
$guest = \App\Support\GuestPortal::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $brandName); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css?v=20250127-guest'); ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg'); ?>" type="image/svg+xml">
    <style>
        :root {
            --guest-primary: #8a6a3f;
            --guest-primary-light: #a67c52;
            --guest-bg: #fafafa;
            --guest-card: #ffffff;
            --guest-text: #1a1a1a;
            --guest-text-light: #666666;
            --guest-border: #e5e5e5;
            --guest-success: #16a34a;
            --guest-warning: #f59e0b;
            --guest-danger: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--guest-bg);
            color: var(--guest-text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .guest-header {
            background: var(--guest-card);
            border-bottom: 1px solid var(--guest-border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .guest-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .guest-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--guest-text);
        }

        .guest-logo img {
            height: 40px;
            width: auto;
        }

        .guest-logo-text {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .guest-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .guest-nav a {
            color: var(--guest-text-light);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .guest-nav a:hover {
            color: var(--guest-primary);
        }

        .guest-nav a.active {
            color: var(--guest-primary);
            font-weight: 500;
        }

        .guest-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--guest-bg);
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .guest-user-name {
            font-weight: 500;
            color: var(--guest-text);
        }

        .guest-logout {
            color: var(--guest-text-light);
            text-decoration: none;
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: background 0.2s;
        }

        .guest-logout:hover {
            background: var(--guest-border);
        }

        .guest-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .guest-page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--guest-text);
        }

        .guest-page-subtitle {
            color: var(--guest-text-light);
            margin-bottom: 2rem;
        }

        .guest-card {
            background: var(--guest-card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--guest-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .guest-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--guest-text);
        }

        .guest-empty {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--guest-text-light);
        }

        .guest-empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .guest-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--guest-primary);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .guest-btn:hover {
            background: var(--guest-primary-light);
        }

        .guest-btn-outline {
            background: transparent;
            border: 1px solid var(--guest-border);
            color: var(--guest-text);
        }

        .guest-btn-outline:hover {
            background: var(--guest-bg);
        }

        .guest-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .guest-badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .guest-badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .guest-badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .guest-badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        @media (max-width: 768px) {
            .guest-header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .guest-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .guest-container {
                padding: 1.5rem 1rem;
            }

            .guest-page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="guest-header">
        <div class="guest-header-content">
            <a href="<?= base_url('guest/portal'); ?>" class="guest-logo">
                <?php if ($logoPath): ?>
                    <img src="<?= asset($logoPath); ?>" alt="<?= htmlspecialchars($brandName); ?>">
                <?php endif; ?>
                <span class="guest-logo-text"><?= htmlspecialchars($brandName); ?></span>
            </a>
            <?php if ($guest): ?>
                <nav class="guest-nav">
                    <a href="<?= base_url('guest/portal'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/portal') ? 'active' : ''; ?>">Dashboard</a>
                    <a href="<?= base_url('guest/orders'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/orders') ? 'active' : ''; ?>">Orders</a>
                    <a href="<?= base_url('guest/folios'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/folios') ? 'active' : ''; ?>">Folios</a>
                    <a href="<?= base_url('guest/contact'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/contact') ? 'active' : ''; ?>">Contact</a>
                    <a href="<?= base_url('guest/notifications'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/notifications') ? 'active' : ''; ?>">Notifications</a>
                    <a href="<?= base_url('guest/profile'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/guest/profile') ? 'active' : ''; ?>">Profile</a>
                </nav>
                <div class="guest-user">
                    <span class="guest-user-name"><?= htmlspecialchars($guest['guest_name'] ?? 'Guest'); ?></span>
                    <a href="<?= base_url('guest/logout'); ?>" class="guest-logout">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="guest-container">
        <?= $slot ?? ''; ?>
    </main>
</body>
</html>

