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
        <h3>Strategic Alerts</h3>
        <ul>
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <li><?= htmlspecialchars($alert); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Action Items</h3>
        <ul>
            <?php foreach ($dashboardData['actions'] as $action): ?>
                <li><?= htmlspecialchars($action); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<?php if (!empty($dashboardData['arrivals']) || !empty($dashboardData['departures'])): ?>
    <div class="flex-panels">
        <?php if (!empty($dashboardData['arrivals'])): ?>
            <article class="card">
                <h3>Arrivals</h3>
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
        <?php if (!empty($dashboardData['departures'])): ?>
            <article class="card">
                <h3>Departures</h3>
                <ul class="list-condensed">
                    <?php foreach ($dashboardData['departures'] as $departure): ?>
                        <?php $room = $departure['display_name'] ?? $departure['room_number'] ?? $departure['room_type_name'] ?? 'Unassigned'; ?>
                        <li>
                            <strong><?= htmlspecialchars($departure['guest_name']); ?></strong>
                            <span><?= htmlspecialchars($room); ?> · <?= date('M j', strtotime($departure['check_out'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endif; ?>
    </div>
<?php endif; ?>

