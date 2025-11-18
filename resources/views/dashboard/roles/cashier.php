<section class="quick-actions">
    <?php foreach ($dashboardData['shortcuts'] as $shortcut): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($shortcut['action']); ?>"><?= htmlspecialchars($shortcut['label']); ?></a>
    <?php endforeach; ?>
</section>
<div class="flex-panels">
    <article class="card">
        <h3>Pending Payments</h3>
        <ul>
            <?php foreach ($dashboardData['pending_payments'] as $payment): ?>
                <li>
                    <strong><?= htmlspecialchars($payment['guest']); ?></strong>
                    <span><?= htmlspecialchars($payment['room']); ?> · <?= htmlspecialchars($payment['balance']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h3>Arrivals</h3>
        <ul>
            <?php foreach ($dashboardData['arrivals'] as $arrival): ?>
                <?php $dateLabel = !empty($arrival['check_in']) ? date('M j', strtotime($arrival['check_in'])) : 'Pending'; ?>
                <li>
                    <strong><?= htmlspecialchars($arrival['guest']); ?></strong>
                    <span><?= htmlspecialchars($arrival['room']); ?> · <?= htmlspecialchars($dateLabel); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<article class="card">
    <h3>Notifications</h3>
    <ul>
        <?php foreach ($dashboardData['notifications'] as $note): ?>
            <li><?= htmlspecialchars($note); ?></li>
        <?php endforeach; ?>
    </ul>
</article>

