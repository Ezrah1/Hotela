<?php
$pageTitle = 'Analytics & Reports';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>Analytics & Reports</h2>
        <p class="page-subtitle">Platform-wide statistics and insights</p>
    </div>
</div>

<div class="sysadmin-stats-grid">
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #667eea20; color: #667eea;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_users']); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #10b98120; color: #10b981;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_bookings_30d']); ?></div>
            <div class="stat-label">Bookings (30 days)</div>
        </div>
    </div>
    
    <div class="sysadmin-stat-card">
        <div class="stat-icon" style="background: #f59e0b20; color: #f59e0b;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['active_licenses']); ?></div>
            <div class="stat-label">Active Licenses</div>
        </div>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Platform Overview</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Detailed analytics and reporting features coming soon.</p>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

