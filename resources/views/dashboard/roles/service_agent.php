<article class="card">
    <h3>Reservations Pipeline</h3>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Guest</th>
            <th>Arrival</th>
            <th>Room</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dashboardData['reservations'] as $res): ?>
            <?php
            $reference = $res['reference'] ?? $res['code'] ?? 'N/A';
            $guest = $res['guest_name'] ?? $res['guest'] ?? 'Guest';
            $room = $res['room'] ?? $res['display_name'] ?? $res['room_number'] ?? $res['room_type_name'] ?? 'Unassigned';
            $arrival = $res['check_in'] ?? $res['status'] ?? '';
            $arrivalTime = $arrival && strtotime($arrival) ? strtotime($arrival) : null;
            ?>
            <tr>
                <td><?= htmlspecialchars($reference); ?></td>
                <td><?= htmlspecialchars($guest); ?></td>
                <td><?= htmlspecialchars($arrivalTime ? date('M j', $arrivalTime) : $arrival); ?></td>
                <td><?= htmlspecialchars($room); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</article>
<div class="flex-panels">
    <article class="card">
        <h3>Room Status</h3>
        <ul>
            <?php foreach ($dashboardData['room_status'] as $room): ?>
                <li>
                    <strong><?= htmlspecialchars($room['room']); ?></strong>
                    <span><?= htmlspecialchars($room['status']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Service Requests</h3>
        <ul>
            <?php foreach ($dashboardData['requests'] as $req): ?>
                <li><?= htmlspecialchars($req); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>

