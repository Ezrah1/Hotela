<div class="flex-panels">
    <article class="card">
        <h3>Attendance</h3>
        <ul>
            <?php foreach ($dashboardData['attendance'] as $entry): ?>
                <li>
                    <strong><?= htmlspecialchars($entry['name']); ?></strong>
                    <span><?= htmlspecialchars($entry['status']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Incidents</h3>
        <ul>
            <?php foreach ($dashboardData['incidents'] as $incident): ?>
                <li><?= htmlspecialchars($incident); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<article class="card">
    <h3>Actions</h3>
    <ul>
        <?php foreach ($dashboardData['actions'] as $action): ?>
            <li><?= htmlspecialchars($action); ?></li>
        <?php endforeach; ?>
    </ul>
</article>

