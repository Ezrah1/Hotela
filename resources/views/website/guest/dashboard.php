<?php
$website = settings('website', []);
$guestName = $guest['guest_name'] ?? 'Guest';
$slot = function () use ($guestName, $guest, $reservations) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Welcome back, <?= htmlspecialchars($guestName); ?></h1>
            <p>Review your stays, orders, and reach concierge support any time.</p>
            <form method="post" action="<?= base_url('guest/logout'); ?>" class="inline-form">
                <input type="hidden" name="redirect" value="<?= base_url('/'); ?>">
                <button class="btn btn-outline btn-small" type="submit">Sign out</button>
            </form>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-grid">
        <article class="card">
            <h2>Your bookings</h2>
            <?php if (!$reservations): ?>
                <p class="empty-state">No bookings linked to this contact yet.</p>
                <a class="btn btn-primary btn-small" href="<?= base_url('booking'); ?>">Make a booking</a>
            <?php else: ?>
                <ul class="reservation-list">
                    <?php foreach ($reservations as $reservation): ?>
                        <li class="reservation-card">
                            <header>
                                <div>
                                    <strong><?= htmlspecialchars($reservation['room_type_name'] ?? 'Room'); ?></strong>
                                    <span><?= htmlspecialchars($reservation['reference']); ?></span>
                                </div>
                                <span class="status status-<?= htmlspecialchars($reservation['status']); ?>"><?= ucfirst($reservation['status']); ?></span>
                            </header>
                            <p><?= date('M j, Y', strtotime($reservation['check_in'])) ?> → <?= date('M j, Y', strtotime($reservation['check_out'])) ?></p>
                            <p><?= htmlspecialchars($reservation['guest_name']); ?> · <?= (int)$reservation['adults']; ?> adults<?php if ((int)$reservation['children'] > 0): ?>, <?= (int)$reservation['children']; ?> children<?php endif; ?></p>
                            <div class="actions">
                                <a class="btn btn-outline btn-small" href="<?= base_url('booking?reference=' . urlencode($reservation['reference'])); ?>">Modify request</a>
                                <a class="btn btn-ghost btn-small" href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>">Call front desk</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
        <article class="card card-outline">
            <h2>Orders & concierge</h2>
            <p>Live order tracking is coming soon. For now, WhatsApp or call our concierge to update dining requests and transport.</p>
            <div class="hero-actions">
                <?php if ($wa = settings('website.contact_whatsapp')): ?>
                    <a class="btn btn-whatsapp" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" rel="noopener">WhatsApp concierge</a>
                <?php endif; ?>
                <a class="btn btn-outline" href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>">Call front desk</a>
            </div>
        </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Guest Portal | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

