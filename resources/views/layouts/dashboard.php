<?php
use App\Support\Auth;

// SECURITY: Validate session integrity before displaying user information
if (isset($_SESSION['__original_user_id']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_id'] != $_SESSION['__original_user_id'] && !isset($_SESSION['__auth_modified'])) {
        error_log('SECURITY ALERT: Session hijacking detected in dashboard layout. Original: ' . $_SESSION['__original_user_id'] . ', Current: ' . $_SESSION['user_id'] . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ', URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        // Restore original immediately
        $_SESSION['user_id'] = $_SESSION['__original_user_id'];
        unset($_SESSION['__auth_modified']);
        // Clear Auth cache
        Auth::clearCache();
    }
}

// SECURITY: NEVER trust $user from view data - ALWAYS get it from Auth::user()
// This prevents session hijacking by overwriting the $user variable
$roleConfig = $roleConfig ?? [];
// Force use Auth::user() - ignore any $user that might have been passed from view data
$authenticatedUser = Auth::check() ? Auth::user() : [];
$user = $authenticatedUser;
$userName = $user['name'] ?? 'Guest';
$userRoleKey = $user['role_key'] ?? ($user['role'] ?? null);
// If user has deprecated 'admin' role, show as 'Director' instead
if ($userRoleKey === 'admin') {
    $userRoleLabel = 'Director';
} else {
    $userRoleLabel = $roleConfig['label'] ?? ($userRoleKey ? ucfirst(str_replace('_', ' ', $userRoleKey)) : 'User');
}

// DEBUG: Log who is actually being displayed
error_log('DEBUG Dashboard Layout - Displaying user: Name=' . $userName . ', ID=' . ($user['id'] ?? 'NONE') . ', Role=' . $userRoleKey . ', SessionUserId=' . ($_SESSION['user_id'] ?? 'NOT SET'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    // Use file modification time for cache busting - ensures fresh CSS on updates
    // This works consistently across local and tunneled access
    $cssPath = BASE_PATH . '/public/assets/css/main.css';
    $cssVersion = file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '?v=' . time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Hotela Dashboard'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css' . $cssVersion); ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg'); ?>" type="image/svg+xml">
</head>
<body class="dashboard-body">
<?php 
// Get all roles assigned to the user
$userRoles = $user['role_keys'] ?? [];
// Fallback to single role_key if role_keys array is empty (backward compatibility)
if (empty($userRoles) && isset($user['role_key'])) {
    $userRoles = [$user['role_key']];
}
// Ensure we always have at least one role
if (empty($userRoles) && $userRoleKey) {
    $userRoles = [$userRoleKey];
}
// Always pass all roles to sidebar for proper link combination
$navLinks = \App\Support\Sidebar::linksFor($userRoleKey, $userRoles); 
?>
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>
<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar" id="dashboardSidebar">
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
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-left">
                <div class="header-breadcrumb">
                    <div class="mobile-brand">
                        <?php
                        $brandName = settings('branding.name', 'Hotela');
                        $adminLogo = settings('branding.admin_logo', settings('branding.logo', 'assets/img/hotela-logo.svg'));
                        ?>
                        <?php if ($adminLogo): ?>
                            <img src="<?= asset($adminLogo); ?>" alt="<?= htmlspecialchars($brandName); ?>" class="mobile-brand-logo">
                        <?php else: ?>
                            <?php $initial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brandName), 0, 1)) ?: 'H'; ?>
                            <span class="mobile-brand-initial"><?= htmlspecialchars($initial); ?></span>
                        <?php endif; ?>
                    </div>
                    <h1><?= htmlspecialchars($roleConfig['label'] ?? 'Dashboard'); ?></h1>
                    <p class="header-subtitle">Welcome back, <?= htmlspecialchars($userName); ?></p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <a href="<?= base_url('staff/dashboard/messages'); ?>" class="header-action-btn" title="Messages">
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
                    <a href="<?= base_url('staff/dashboard/notifications'); ?>" class="header-action-btn" id="notificationLink" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php
                        $notificationRepo = new \App\Repositories\NotificationRepository();
                        $unreadNotifications = $notificationRepo->getUnreadCount($userRoleKey);
                        if ($unreadNotifications > 0):
                        ?>
                            <span class="badge" id="notificationBadge"><?= $unreadNotifications; ?></span>
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
                        <a href="<?= base_url('staff/dashboard'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            Dashboard
                        </a>
                        <a href="<?= base_url('staff/dashboard/messages'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Messages
                        </a>
                        <?php if (in_array($userRoleKey, ['admin'])): ?>
                        <a href="<?= base_url('staff/admin/settings'); ?>" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                            </svg>
                            Settings
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url('staff/logout'); ?>" class="dropdown-item dropdown-item-danger">
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
                        <li><a href="<?= base_url('staff/dashboard'); ?>">Dashboard</a></li>
                        <li><a href="<?= base_url('staff/dashboard/bookings'); ?>">Bookings</a></li>
                        <li><a href="<?= base_url('staff/dashboard/pos'); ?>">POS System</a></li>
                        <?php if (in_array($userRoleKey, ['admin'])): ?>
                        <li><a href="<?= base_url('staff/admin/settings'); ?>">Settings</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="<?= base_url('staff/dashboard/messages'); ?>">Messages</a></li>
                        <li><a href="<?= base_url('staff/dashboard/announcements'); ?>">Announcements</a></li>
                        <li><a href="<?= base_url('staff/dashboard/notifications'); ?>">Notifications</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>System</h4>
                    <ul>
                        <li><a href="<?= base_url(); ?>" target="_blank">View Website</a></li>
                        <li><a href="<?= base_url('staff/dashboard/reports/sales'); ?>">Reports</a></li>
                        <li><a href="<?= base_url('staff/dashboard/tasks'); ?>">Tasks</a></li>
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
// User menu toggle - separate from mobile menu
(function() {
    function initUserMenu() {
        const toggle = document.getElementById('userMenuToggle');
        const dropdown = document.getElementById('userDropdown');
        
        if (!toggle || !dropdown) {
            return;
        }
        
        // Ensure dropdown is closed on page load/refresh
        dropdown.classList.remove('active');
        
        function positionDropdown() {
            if (dropdown.classList.contains('active')) {
                try {
                    const toggleRect = toggle.getBoundingClientRect();
                    // Use fixed positioning to float over content
                    dropdown.style.position = 'fixed';
                    dropdown.style.top = (toggleRect.bottom + 8) + 'px';
                    dropdown.style.right = (window.innerWidth - toggleRect.right) + 'px';
                    dropdown.style.left = 'auto';
                } catch (e) {
                    // Silent fail
                }
            }
        }
        
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const wasActive = dropdown.classList.contains('active');
            dropdown.classList.toggle('active');
            if (!wasActive && dropdown.classList.contains('active')) {
                // Just opened - position it
                setTimeout(positionDropdown, 10);
            }
        }, true); // Use capture phase to ensure it runs first
        
        // Close dropdown when clicking outside - but don't interfere with mobile menu
        document.addEventListener('click', function(e) {
            // Don't close if clicking on mobile menu toggle or sidebar
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('dashboardSidebar');
            if (mobileToggle && mobileToggle.contains(e.target)) {
                return; // Let mobile menu handle it
            }
            if (sidebar && sidebar.contains(e.target)) {
                return; // Let sidebar handle it
            }
            
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserMenu);
    } else {
        initUserMenu();
    }
})();

// Notification polling and sound alerts
document.addEventListener('DOMContentLoaded', function() {
    let lastCheckTime = Math.floor(Date.now() / 1000);
    let notificationSound = null;
    
    // Create notification sound (using Web Audio API for a simple beep)
    function createNotificationSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            console.log('Audio not supported:', e);
        }
    }
    
    // Play notification sound
    function playNotificationSound() {
        createNotificationSound();
    }
    
    // Show browser notification
    function showBrowserNotification(title, message) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '<?= asset("images/favicon.png"); ?>',
                badge: '<?= asset("images/favicon.png"); ?>',
                tag: 'hotela-notification',
            });
        } else if ('Notification' in window && Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    new Notification(title, {
                        body: message,
                        icon: '<?= asset("images/favicon.png"); ?>',
                        badge: '<?= asset("images/favicon.png"); ?>',
                        tag: 'hotela-notification',
                    });
                }
            });
        }
    }
    
    // Update notification badge
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        const notificationLink = document.getElementById('notificationLink');
        
        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            } else if (notificationLink) {
                const newBadge = document.createElement('span');
                newBadge.id = 'notificationBadge';
                newBadge.className = 'badge';
                newBadge.textContent = count;
                notificationLink.appendChild(newBadge);
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
    }
    
    // Check for new notifications
    function checkNotifications() {
        fetch('<?= base_url("staff/dashboard/notifications/check"); ?>?last_check=' + lastCheckTime)
            .then(response => response.json())
            .then(data => {
                if (data.unread_count !== undefined) {
                    updateNotificationBadge(data.unread_count);
                }
                
                // If there are new notifications, play sound and show browser notification
                if (data.new_notifications && data.new_notifications.length > 0) {
                    playNotificationSound();
                    
                    // Show notification for the first new notification
                    const firstNotification = data.new_notifications[0];
                    showBrowserNotification(firstNotification.title, firstNotification.message);
                }
                
                // Update last check time
                if (data.timestamp) {
                    lastCheckTime = data.timestamp;
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }
    
    // Request notification permission on page load
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Check notifications immediately, then every 10 seconds
    checkNotifications();
    setInterval(checkNotifications, 10000);

    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const dashboardSidebar = document.getElementById('dashboardSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');

    if (mobileMenuToggle && dashboardSidebar) {
        function toggleMobileMenu() {
            dashboardSidebar.classList.toggle('active');
            if (mobileSidebarOverlay) {
                mobileSidebarOverlay.classList.toggle('active');
            }
            document.body.style.overflow = dashboardSidebar.classList.contains('active') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            dashboardSidebar.classList.remove('active');
            if (mobileSidebarOverlay) {
                mobileSidebarOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }

        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        
        if (mobileSidebarOverlay) {
            mobileSidebarOverlay.addEventListener('click', closeMobileMenu);
        }

        // Close menu when clicking on a link (mobile only)
        // Use event delegation to handle dynamically added links
        if (window.innerWidth <= 1023) {
            dashboardSidebar.addEventListener('click', (e) => {
                if (e.target.tagName === 'A' || e.target.closest('a')) {
                    setTimeout(closeMobileMenu, 100);
                }
            });
        }

        // Close menu on window resize if it becomes desktop size
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1023) {
                closeMobileMenu();
            }
        });
    }

    // Auto-wrap tables in responsive containers if not already wrapped
    document.addEventListener('DOMContentLoaded', function() {
        const tables = document.querySelectorAll('table.table-lite, table.data-table, table.modern-table');
        tables.forEach(table => {
            // Check if already wrapped
            if (table.parentElement.classList.contains('table-responsive')) {
                return;
            }
            
            // Check if table has a wrapper that's not table-responsive
            const wrapper = table.parentElement;
            if (wrapper.tagName === 'DIV' && !wrapper.classList.contains('table-responsive')) {
                // Create new wrapper
                const responsiveWrapper = document.createElement('div');
                responsiveWrapper.className = 'table-responsive';
                table.parentElement.insertBefore(responsiveWrapper, table);
                responsiveWrapper.appendChild(table);
            } else {
                // Wrap the table
                const responsiveWrapper = document.createElement('div');
                responsiveWrapper.className = 'table-responsive';
                table.parentElement.insertBefore(responsiveWrapper, table);
                responsiveWrapper.appendChild(table);
            }
        });

        // Add data-label attributes to table cells if missing (for mobile card view)
        tables.forEach(table => {
            const headers = Array.from(table.querySelectorAll('thead th'));
            if (headers.length === 0) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (!cell.hasAttribute('data-label') && headers[index]) {
                        cell.setAttribute('data-label', headers[index].textContent.trim());
                    }
                });
            });
        });

        // Ensure forms use responsive classes
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (!input.classList.contains('modern-input') && input.type !== 'hidden' && input.type !== 'submit' && input.type !== 'button') {
                    if (input.tagName === 'SELECT') {
                        input.classList.add('modern-select');
                    } else {
                        input.classList.add('modern-input');
                    }
                }
            });
        });
    });
});
</script>
<script src="<?= asset('js/image-upload.js'); ?>"></script>
</body>
</html>

