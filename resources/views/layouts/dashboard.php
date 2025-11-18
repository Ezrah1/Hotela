<?php
use App\Support\Auth;

$roleConfig = $roleConfig ?? [];
$user = $user ?? Auth::user() ?? [];
$userName = $user['name'] ?? 'Guest';
$userRoleKey = $user['role_key'] ?? ($user['role'] ?? null);
$userRoleLabel = $roleConfig['label'] ?? ($userRoleKey ? ucfirst(str_replace('_', ' ', $userRoleKey)) : 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $assetVersion = '?v=20251117-pos'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Hotela Dashboard'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css' . $assetVersion); ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg'); ?>" type="image/svg+xml">
</head>
<body class="dashboard-body">
<?php $navLinks = \App\Support\Sidebar::linksFor($userRoleKey); ?>
<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <div class="brand-mini">
            <?php
            $brandName = settings('branding.name', 'Hotela');
            $brandTagline = settings('branding.tagline', 'Integrated Hospitality OS');
            $adminLogo = settings('branding.admin_logo', settings('branding.logo', 'assets/img/hotela-logo.svg'));
            ?>
            <?php if ($adminLogo): ?>
                <img src="<?= asset($adminLogo); ?>" alt="<?= htmlspecialchars($brandName); ?> logo" style="height:48px;width:auto;object-fit:contain;border-radius:10px;">
            <?php else: ?>
                <?php $initial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brandName), 0, 1)) ?: 'H'; ?>
                <span class="brand-mini__badge"><?= htmlspecialchars($initial); ?></span>
            <?php endif; ?>
            <div class="brand-mini__text">
                <strong><?= htmlspecialchars($brandName); ?></strong>
                <small><?= htmlspecialchars($brandTagline); ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navLinks as $link): ?>
                <a href="<?= base_url($link['href']); ?>" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/' . trim($link['href'], '/')) ? 'active' : ''; ?>">
                    <?= htmlspecialchars($link['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="dashboard-content">
        <header class="dashboard-header">
            <div class="header-left">
                <div class="header-breadcrumb">
                    <h1><?= htmlspecialchars($roleConfig['label'] ?? 'Dashboard'); ?></h1>
                    <p class="header-subtitle">Welcome back, <?= htmlspecialchars($userName); ?></p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <a href="<?= base_url('dashboard/messages'); ?>" class="header-action-btn" title="Messages">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <?php
                        $messageRepo = new \App\Repositories\MessageRepository();
                        $unreadMessages = $messageRepo->getUnreadCount($user['id'] ?? 0, $userRoleKey);
                        if ($unreadMessages > 0):
                        ?>
                            <span class="badge"><?= $unreadMessages; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= base_url('dashboard/notifications'); ?>" class="header-action-btn" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php
                        $notificationRepo = new \App\Repositories\NotificationRepository();
                        $unreadNotifications = $notificationRepo->getUnreadCount($userRoleKey);
                        if ($unreadNotifications > 0):
                        ?>
                            <span class="badge"><?= $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="user-menu">
                    <div class="user-chip" id="userMenuToggle">
                        <span class="user-avatar"><?= strtoupper(substr($userName, 0, 1)); ?></span>
                        <div class="user-info">
                            <strong><?= htmlspecialchars($userName); ?></strong>
                            <small><?= htmlspecialchars($userRoleLabel); ?></small>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="<?= base_url('dashboard'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            Dashboard
                        </a>
                        <a href="<?= base_url('dashboard/messages'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Messages
                        </a>
                        <?php if (in_array($userRoleKey, ['admin'])): ?>
                        <a href="<?= base_url('admin/settings'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                            </svg>
                            Settings
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url('logout'); ?>" class="dropdown-item dropdown-item-danger">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <section class="dashboard-slot">
            <?= $slot ?? ''; ?>
        </section>
        <footer class="dashboard-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?= htmlspecialchars(settings('branding.name', 'Hotela')); ?></h4>
                    <p><?= htmlspecialchars(settings('branding.tagline', 'Integrated Hospitality OS')); ?></p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?= base_url('dashboard'); ?>">Dashboard</a></li>
                        <li><a href="<?= base_url('dashboard/bookings'); ?>">Bookings</a></li>
                        <li><a href="<?= base_url('dashboard/pos'); ?>">POS System</a></li>
                        <?php if (in_array($userRoleKey, ['admin'])): ?>
                        <li><a href="<?= base_url('admin/settings'); ?>">Settings</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="<?= base_url('dashboard/messages'); ?>">Messages</a></li>
                        <li><a href="<?= base_url('dashboard/announcements'); ?>">Announcements</a></li>
                        <li><a href="<?= base_url('dashboard/notifications'); ?>">Notifications</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>System</h4>
                    <ul>
                        <li><a href="<?= base_url(); ?>" target="_blank">View Website</a></li>
                        <li><a href="<?= base_url('dashboard/reports/sales'); ?>">Reports</a></li>
                        <li><a href="<?= base_url('dashboard/tasks'); ?>">Tasks</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-meta">
                    <span>Version <?= htmlspecialchars(settings('branding.version', '1.0.0')); ?></span>
                    <span>•</span>
                    <span>Logged in as: <?= htmlspecialchars($userRoleLabel); ?></span>
                </div>
                <p class="footer-copyright">&copy; <?= date('Y'); ?> <?= htmlspecialchars(settings('branding.name', 'Hotela')); ?>. All rights reserved.</p>
            </div>
        </footer>
    </main>
</div>
<script>
// User menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('userMenuToggle');
    const dropdown = document.getElementById('userDropdown');
    
    if (toggle && dropdown) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            dropdown.classList.remove('active');
        });
        
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
<script src="<?= asset('js/image-upload.js'); ?>"></script>
</body>
</html>

