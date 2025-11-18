<article class="card">
    <h3>Room Queue</h3>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Room</th>
            <th>Type</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dashboardData['rooms'] as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?></td>
                <td><?= htmlspecialchars($room['room_type_name']); ?></td>
                <td><?= htmlspecialchars(str_replace('_', ' ', $room['status'])); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</article>
<article class="card">
    <h3>Tasks</h3>
    <ul>
        <?php foreach ($dashboardData['tasks'] as $task): ?>
            <li><?= htmlspecialchars($task); ?></li>
        <?php endforeach; ?>
    </ul>
</article>
<?php if (!empty($dashboardData['notifications'])): ?>
    <article class="card">
        <h3>Notifications</h3>
        <ul>
        <?php foreach ($dashboardData['notifications'] as $notification): ?>
            <li>
                <strong><?= htmlspecialchars($notification['title']); ?></strong>
                <span><?= htmlspecialchars($notification['message']); ?></span>
                <small><?= htmlspecialchars($notification['created_at']); ?></small>
            </li>
        <?php endforeach; ?>
        </ul>
    </article>
<?php endif; ?>

