<?php
$brandName = 'Hotela';
$logoPath = asset('assets/img/hotela-logo.svg');
$admin = $admin ?? [];
$pageTitle = $pageTitle ?? 'System Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?> | <?= htmlspecialchars($brandName); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
    <style>
        .sysadmin-sidebar {
            background: #1e293b;
            color: white;
            width: 260px;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        
        .sysadmin-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sysadmin-brand img {
            width: 200px;
            height: auto;
            max-width: 100%;
            display: block;
            object-fit: contain;
        }
        
        .sysadmin-brand-text {
            flex: 1;
        }
        
        .sysadmin-brand-text strong {
            display: block;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .sysadmin-brand-text small {
            display: block;
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .sysadmin-nav {
            padding: 1rem 0;
        }
        
        .sysadmin-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sysadmin-nav a:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .sysadmin-nav a.active {
            background: rgba(102, 126, 234, 0.2);
            color: white;
            border-left-color: #667eea;
        }
        
        .sysadmin-nav a svg {
            width: 18px;
            height: 18px;
        }
        
        .sysadmin-content {
            margin-left: 260px;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .sysadmin-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sysadmin-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .sysadmin-header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .sysadmin-main {
            padding: 2rem;
        }
        
        .sysadmin-user-menu {
            position: relative;
        }
        
        .sysadmin-user-chip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .sysadmin-user-chip:hover {
            background: #e2e8f0;
        }
        
        .sysadmin-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .sysadmin-user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1000;
        }
        
        .sysadmin-user-menu.active .sysadmin-user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .sysadmin-dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #475569;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .sysadmin-dropdown-item:hover {
            background: #f1f5f9;
        }
        
        .sysadmin-dropdown-item.danger {
            color: #dc2626;
        }
        
        .sysadmin-dropdown-item.danger:hover {
            background: #fef2f2;
        }
    </style>
</head>
<body>
    <div class="sysadmin-sidebar">
        <div class="sysadmin-brand">
            <img src="<?= $logoPath; ?>" alt="<?= htmlspecialchars($brandName); ?>">
        </div>
        <nav class="sysadmin-nav">
            <a href="<?= base_url('sysadmin/dashboard'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/dashboard') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/tenants') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Overview
            </a>
            <a href="<?= base_url('sysadmin/tenants'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/tenants') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Tenants
            </a>
            <a href="<?= base_url('sysadmin/licenses'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/licenses') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Licenses
            </a>
            <a href="<?= base_url('sysadmin/logs'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/logs') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Audit Logs
            </a>
            <a href="<?= base_url('sysadmin/health'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/health') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Health Checks
            </a>
            <a href="<?= base_url('sysadmin/analytics'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/analytics') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Analytics
            </a>
            <a href="<?= base_url('sysadmin/packages'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/packages') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Packages
            </a>
            <a href="<?= base_url('sysadmin/settings'); ?>" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sysadmin/settings') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                </svg>
                Settings
            </a>
            <a href="<?= base_url('sysadmin/logout'); ?>" style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </nav>
    </div>
    
    <div class="sysadmin-content">
        <header class="sysadmin-header">
            <div>
                <h1><?= htmlspecialchars($pageTitle); ?></h1>
            </div>
            <div class="sysadmin-header-actions">
                <div class="sysadmin-user-menu" id="userMenu">
                    <div class="sysadmin-user-chip" onclick="document.getElementById('userMenu').classList.toggle('active')">
                        <div class="sysadmin-user-avatar">
                            <?= strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span><?= htmlspecialchars($admin['username'] ?? 'Admin'); ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="sysadmin-user-dropdown">
                        <a href="<?= base_url('sysadmin/settings'); ?>" class="sysadmin-dropdown-item">Settings</a>
                        <a href="<?= base_url('sysadmin/logout'); ?>" class="sysadmin-dropdown-item danger">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="sysadmin-main">
            <?= $slot ?? ''; ?>
        </main>
    </div>
    
    <script>
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('userMenu');
            if (userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });
    </script>
</body>
</html>

