<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SysAdmin'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
</head>
<body class="dashboard-body">
<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <div class="brand-mini">
            <img src="<?= asset('assets/img/hotela-logo.svg'); ?>" alt="SysAdmin">
            <div>
                <strong>System Ops</strong>
                <small>Super Admin</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= base_url('sysadmin/dashboard'); ?>" class="active">Overview</a>
            <a href="#">Tenants</a>
            <a href="#">Health Checks</a>
            <a href="#">Logs</a>
            <a href="<?= base_url('sysadmin/logout'); ?>">Logout</a>
        </nav>
    </aside>
    <main class="dashboard-content">
        <header class="dashboard-header">
            <div>
                <h1><?= htmlspecialchars($pageTitle ?? 'System Control Center'); ?></h1>
                <p>Monitor platform health and tenants.</p>
            </div>
        </header>
        <section class="dashboard-slot kpi-grid">
            <article class="card kpi">
                <h4>Active Hotels</h4>
                <p class="value"><?= htmlspecialchars($stats['hotels'] ?? 0); ?></p>
            </article>
            <article class="card kpi">
                <h4>Uptime (30d)</h4>
                <p class="value"><?= htmlspecialchars($stats['uptime'] ?? ''); ?></p>
            </article>
            <article class="card kpi">
                <h4>Pending Updates</h4>
                <p class="value"><?= htmlspecialchars($stats['pending_updates'] ?? 0); ?></p>
            </article>
        </section>
    </main>
</div>
</body>
</html>

