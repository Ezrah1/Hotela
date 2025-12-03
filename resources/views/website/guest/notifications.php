<?php
ob_start();
?>
<div>
    <h1 class="guest-page-title">Notifications</h1>
    <p class="guest-page-subtitle">Stay updated on your bookings and orders</p>

    <div class="guest-card">
        <?php if (empty($notifications)): ?>
            <div class="guest-empty">
                <div class="guest-empty-icon">🔔</div>
                <p>No notifications</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--guest-text-light);">
                    You'll see updates about your bookings and orders here
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($notifications as $notification): ?>
                    <div style="padding: 1.25rem; background: var(--guest-bg); border-radius: 0.5rem; border: 1px solid var(--guest-border); border-left: 4px solid var(--guest-primary);">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                            <div style="flex: 1;">
                                <p style="font-weight: 500; margin-bottom: 0.5rem; color: var(--guest-text);">
                                    <?= htmlspecialchars($notification['message']); ?>
                                </p>
                                <p style="font-size: 0.85rem; color: var(--guest-text-light);">
                                    <?= date('M j, Y g:i A', strtotime($notification['date'])); ?>
                                </p>
                            </div>
                            <?php if (!empty($notification['link'])): ?>
                                <a href="<?= htmlspecialchars($notification['link']); ?>" class="guest-btn guest-btn-outline" style="font-size: 0.85rem; padding: 0.5rem 1rem; white-space: nowrap;">
                                    View
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>

