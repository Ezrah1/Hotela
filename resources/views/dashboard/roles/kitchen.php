<article class="card">
    <h3>Kitchen Order Tickets</h3>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Ticket</th>
            <th>Items</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dashboardData['orders'] as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['ticket']); ?></td>
                <td><?= htmlspecialchars($order['items']); ?></td>
                <td><?= htmlspecialchars($order['status']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</article>
<article class="card">
    <h3>Inventory Alerts</h3>
    <ul>
        <?php foreach ($dashboardData['inventory_alerts'] as $alert): ?>
            <li><?= htmlspecialchars($alert); ?></li>
        <?php endforeach; ?>
    </ul>
</article>

