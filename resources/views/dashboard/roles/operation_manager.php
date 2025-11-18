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
        <h3>Operational Alerts</h3>
        <ul>
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <li><?= htmlspecialchars($alert); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Action Queue</h3>
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
<?php if (!empty($dashboardData['housekeeping_queue'])): ?>
    <article class="card">
        <h3>Housekeeping Queue</h3>
        <ul class="list-condensed">
            <?php foreach ($dashboardData['housekeeping_queue'] as $task): ?>
                <?php $room = $task['display_name'] ?? $task['room_number'] ?? $task['room_type_name'] ?? 'Room'; ?>
                <li>
                    <strong><?= htmlspecialchars($room); ?></strong>
                    <span><?= htmlspecialchars('Floor ' . ($task['floor'] ?? '-')); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
<?php endif; ?>
<?php if (!empty($dashboardData['low_stock'])): ?>
    <article class="card">
        <h3>Low Stock Watch</h3>
        <table class="table-lite">
            <thead>
            <tr>
                <th>Item</th>
                <th>Location</th>
                <th>Remaining</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dashboardData['low_stock'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']); ?></td>
                    <td><?= htmlspecialchars($item['location']); ?></td>
                    <td><?= htmlspecialchars($item['quantity']); ?> / <?= htmlspecialchars($item['reorder_point']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
<?php endif; ?>
<?php if (!empty($dashboardData['po_pending']) || !empty($dashboardData['recent_movements'])): ?>
<div class="flex-panels">
    <?php if (!empty($dashboardData['po_pending'])): ?>
    <article class="card">
        <h3>Purchasing</h3>
        <p>POs awaiting approval: <strong><?= (int)$dashboardData['po_pending']; ?></strong></p>
        <a class="btn btn-outline btn-small" href="<?= base_url('dashboard/inventory/requisitions'); ?>">Open Requisitions</a>
    </article>
    <?php endif; ?>
    <?php if (!empty($dashboardData['recent_movements'])): ?>
    <article class="card">
        <h3>Today’s Stock Movements</h3>
        <ul class="list-condensed">
            <?php foreach ($dashboardData['recent_movements'] as $m): ?>
                <li>
                    <strong><?= htmlspecialchars(ucfirst($m['type'])); ?></strong>
                    <?= htmlspecialchars($m['item_name']); ?> — <?= htmlspecialchars(number_format((float)$m['quantity'], 2)); ?>
                    <small>(<?= htmlspecialchars($m['location_name']); ?> · <?= htmlspecialchars(date('H:i', strtotime($m['created_at']))); ?>)</small>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>
</div>
<?php endif; ?>

