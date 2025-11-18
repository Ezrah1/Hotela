<div class="flex-panels">
    <article class="card">
        <h3>Maintenance Tasks</h3>
        <ul>
            <?php foreach ($dashboardData['tasks'] as $task): ?>
                <li>
                    <strong><?= htmlspecialchars($task['task']); ?></strong>
                    <span><?= htmlspecialchars($task['status']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Equipment Issues</h3>
        <ul>
            <?php foreach ($dashboardData['issues'] as $issue): ?>
                <li><?= htmlspecialchars($issue); ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>

