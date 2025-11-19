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
        <h3>Admin Tasks</h3>
        <ul>
            <?php foreach ($dashboardData['actions'] as $action): ?>
                <li><?= htmlspecialchars($action); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<article class="card">
    <h3>Quick Configuration Links</h3>
    <div class="quick-links">
        <a href="<?= base_url('staff/admin/settings?tab=branding'); ?>">Branding</a>
        <a href="<?= base_url('staff/admin/settings?tab=pos'); ?>">POS</a>
        <a href="<?= base_url('staff/admin/settings?tab=notifications'); ?>">Notifications</a>
        <a href="<?= base_url('staff/admin/settings?tab=security'); ?>">Security</a>
    </div>
</article>
<?php if (!empty($dashboardData['arrivals'])): ?>
    <article class="card">
        <h3>Arrivals (Next 48h)</h3>
        <ul class="list-condensed">
            <?php foreach ($dashboardData['arrivals'] as $arrival): ?>
                <?php $room = $arrival['display_name'] ?? $arrival['room_number'] ?? $arrival['room_type_name'] ?? 'Unassigned'; ?>
                <li>
                    <strong><?= htmlspecialchars($arrival['guest_name']); ?></strong>
                    <span><?= htmlspecialchars($room); ?> · <?= date('M j', strtotime($arrival['check_in'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
<?php endif; ?>
<?php if (!empty($dashboardData['pending_balances'])): ?>
    <article class="card">
        <h3>Pending Balances</h3>
        <table class="table-lite">
            <thead>
            <tr>
                <th>Guest</th>
                <th>Room</th>
                <th>Balance</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dashboardData['pending_balances'] as $balance): ?>
                <?php $room = $balance['display_name'] ?? $balance['room_number'] ?? 'Unassigned'; ?>
                <tr>
                    <td><?= htmlspecialchars($balance['guest_name']); ?></td>
                    <td><?= htmlspecialchars($room); ?></td>
                    <td><?= htmlspecialchars(format_currency((float)$balance['balance'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
<?php endif; ?>


