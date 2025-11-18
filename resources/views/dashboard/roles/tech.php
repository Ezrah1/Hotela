<div class="kpi-grid">
    <?php foreach ($dashboardData['kpis'] as $kpi): ?>
        <article class="card kpi">
            <h4><?= htmlspecialchars($kpi['label']); ?></h4>
            <p class="value"><?= htmlspecialchars($kpi['value']); ?></p>
            <span class="trend"><?= htmlspecialchars($kpi['trend']); ?></span>
        </article>
    <?php endforeach; ?>
</div>
<div class="flex-panels">
    <article class="card">
        <h3>System Alerts</h3>
        <ul>
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <li><?= htmlspecialchars($alert); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Immediate Actions</h3>
        <ul>
            <?php foreach ($dashboardData['actions'] as $action): ?>
                <li><?= htmlspecialchars($action); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<article class="card">
    <h3>Maintenance Tools</h3>
    <div class="workflow-grid">
        <button class="btn btn-primary">Trigger Manual Backup</button>
        <button class="btn btn-outline">View System Logs</button>
    </div>
</article>

