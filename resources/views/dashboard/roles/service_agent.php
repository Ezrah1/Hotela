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
        <?php 
        $reservations = $dashboardData['reservations'] ?? [];
        if (empty($reservations)): ?>
            <tr>
                <td colspan="4" class="text-muted" style="text-align: center; padding: 2rem;">
                    No upcoming reservations.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($reservations as $res): ?>
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
        <?php endif; ?>
        </tbody>
    </table>
</article>
<div class="flex-panels">
    <article class="card">
        <h3>Room Status</h3>
        <ul>
            <?php 
            $roomStatus = $dashboardData['room_status'] ?? [];
            if (empty($roomStatus)): ?>
                <li class="text-muted">All rooms are clean.</li>
            <?php else: ?>
                <?php foreach ($roomStatus as $room): ?>
                    <li>
                        <strong><?= htmlspecialchars($room['room'] ?? 'Room'); ?></strong>
                        <span><?= htmlspecialchars($room['status'] ?? 'Unknown'); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Service Requests</h3>
        <ul>
            <?php 
            $requests = $dashboardData['requests'] ?? [];
            if (empty($requests)): ?>
                <li class="text-muted">No service requests at this time.</li>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <?php if (is_array($req)): ?>
                        <li>
                            <strong><?= htmlspecialchars($req['title'] ?? $req['message'] ?? 'Request'); ?></strong>
                            <?php if (isset($req['message']) && $req['message'] !== ($req['title'] ?? '')): ?>
                                <br><small><?= htmlspecialchars($req['message']); ?></small>
                            <?php endif; ?>
                        </li>
                    <?php else: ?>
                        <li><?= htmlspecialchars($req); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </article>
</div>

