<div class="kpi-grid">
    <?php foreach ($dashboardData['kpis'] as $kpi): ?>
        <article class="card kpi">
            <h4><?= htmlspecialchars($kpi['label']); ?></h4>
            <p class="value"><?= htmlspecialchars($kpi['value']); ?></p>
            <span class="trend"><?= htmlspecialchars($kpi['trend']); ?></span>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!empty($dashboardData['po_pending']) || !empty($dashboardData['recent_movements'])): ?>
<div class="flex-panels">
    <?php if (!empty($dashboardData['po_pending'])): ?>
    <article class="card">
        <h3>Purchase Orders</h3>
        <p>Awaiting approval: <strong><?= (int)$dashboardData['po_pending']; ?></strong></p>
        <a class="btn btn-outline btn-small" href="<?= base_url('staff/dashboard/inventory/requisitions'); ?>">Review POs</a>
    </article>
    <?php endif; ?>
    <?php if (!empty($dashboardData['recent_movements'])): ?>
    <article class="card">
        <h3>Recent Stock Movements</h3>
        <ul>
            <?php foreach ($dashboardData['recent_movements'] as $m): ?>
                <li>
                    <strong><?= htmlspecialchars(ucfirst($m['type'])); ?></strong>
                    <?= htmlspecialchars($m['item_name']); ?> — <?= htmlspecialchars(number_format((float)$m['quantity'], 2)); ?>
                    <small>(<?= htmlspecialchars($m['location_name']); ?> · <?= htmlspecialchars(date('M j H:i', strtotime($m['created_at']))); ?>)</small>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>
</div>
<?php endif; ?>
<div class="flex-panels">
    <article class="card">
        <h3>Finance Alerts</h3>
        <ul>
            <?php foreach ($dashboardData['alerts'] as $alert): ?>
                <li><?= htmlspecialchars($alert); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Pending Actions</h3>
        <ul>
            <?php foreach ($dashboardData['actions'] as $action): ?>
                <li><?= htmlspecialchars($action); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<article class="card">
    <h3>Workflow</h3>
    <p>Approve or reject items routed from departments.</p>
    <div class="workflow-grid">
        <button class="btn btn-primary">Approve Supplier Invoice</button>
        <button class="btn btn-outline">Review Expense Entry</button>
    </div>
</article>
<article class="card">
    <h3>Inventory Snapshot</h3>
    <p>Valuation: <strong>KES <?= number_format($dashboardData['inventory_value'] ?? 0, 2); ?></strong></p>
    <?php if (!empty($dashboardData['low_stock'])): ?>
        <ul>
            <?php foreach ($dashboardData['low_stock'] as $item): ?>
                <li><?= htmlspecialchars($item['name']); ?> (<?= htmlspecialchars($item['quantity']); ?> left)</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>All items above reorder points.</p>
    <?php endif; ?>
</article>
<?php if (!empty($dashboardData['pending_payments'])): ?>
    <article class="card">
        <h3>Open Folios</h3>
        <table class="table-lite">
            <thead>
            <tr>
                <th>Guest</th>
                <th>Room</th>
                <th>Balance</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dashboardData['pending_payments'] as $item): ?>
                <?php $room = $item['display_name'] ?? $item['room_number'] ?? 'Unassigned'; ?>
                <tr>
                    <td><?= htmlspecialchars($item['guest_name']); ?></td>
                    <td><?= htmlspecialchars($room); ?></td>
                    <td><?= htmlspecialchars(format_currency((float)$item['balance'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
<?php endif; ?>

