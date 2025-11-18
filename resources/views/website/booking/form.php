<?php
$website = settings('website', []);
$guest = $guest ?? \App\Support\GuestPortal::user();
$slot = function () use ($filters, $availability, $guest) {
    ob_start(); ?>
    <section class="booking-hero">
        <div class="container">
            <h1>Book your stay</h1>
            <p>Provide your contact details once below and we’ll use them for this reservation.</p>
        </div>
    </section>
    <section class="guest-profile-card">
        <div class="container">
            <div class="guest-profile-grid" id="guestProfile">
                <label>
                    <span>Full name</span>
                    <input type="text" name="guest_profile_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>" placeholder="e.g. Jane Mwangi">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="guest_profile_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>" placeholder="you@example.com">
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="guest_profile_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>" placeholder="+254700000000">
                </label>
            </div>
            <p class="guest-profile-hint">An account will be created or updated for you automatically after checkout.</p>
        </div>
    </section>
    <section class="booking-search">
        <div class="container">
            <form method="get" action="<?= base_url('booking'); ?>" class="booking-filters">
                <label>
                    <span>Check-in</span>
                    <input type="date" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>" min="<?= date('Y-m-d'); ?>">
                </label>
                <label>
                    <span>Check-out</span>
                    <input type="date" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>" min="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                </label>
                <label>
                    <span>Adults</span>
                    <input type="number" name="adults" min="1" value="<?= htmlspecialchars($filters['adults']); ?>">
                </label>
                <label>
                    <span>Children</span>
                    <input type="number" name="children" min="0" value="<?= htmlspecialchars($filters['children']); ?>">
                </label>
                <button class="btn btn-primary" type="submit">Check availability</button>
            </form>

            <?php if (!empty($_GET['success'])): ?>
                <div class="alert success">
                    Booking received! Reference: <strong><?= htmlspecialchars($_GET['ref'] ?? ''); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="booking-results">
        <div class="container">
            <?php foreach ($availability as $option): ?>
                <article class="card">
                    <header class="booking-card-header">
                        <div>
                            <h3><?= htmlspecialchars($option['type']['name']); ?></h3>
                            <p><?= htmlspecialchars($option['type']['description'] ?? ''); ?></p>
                        </div>
                        <div class="rate">
                            From <strong>KES <?= number_format($option['type']['base_rate'], 2); ?></strong> / night
                        </div>
                    </header>
                    <div class="booking-card-body">
                        <?php if (!empty($option['rooms'])): ?>
                            <p>Available rooms:</p>
                            <ul class="available-rooms">
                                <?php foreach ($option['rooms'] as $room): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($room['display_name'] ?? $room['room_number']); ?></strong>
                                        <span><?= htmlspecialchars($room['floor'] ? 'Floor ' . $room['floor'] : ''); ?></span>
                                        <form method="post" action="<?= base_url('booking'); ?>">
                                            <input type="hidden" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>">
                                            <input type="hidden" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>">
                                            <input type="hidden" name="adults" value="<?= htmlspecialchars($filters['adults']); ?>">
                                            <input type="hidden" name="children" value="<?= htmlspecialchars($filters['children']); ?>">
                                            <input type="hidden" name="room_type_id" value="<?= (int)$option['type']['id']; ?>">
                                            <input type="hidden" name="room_id" value="<?= (int)$room['id']; ?>">
                                            <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>">
                                            <input type="hidden" name="guest_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>">
                                            <input type="hidden" name="guest_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>">
                                            <button class="btn btn-primary booking-submit-form" type="submit">Book Room <?= htmlspecialchars($room['room_number']); ?></button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No specific rooms free right now, but you can request this room type.</p>
                            <form method="post" action="<?= base_url('booking'); ?>">
                                <input type="hidden" name="check_in" value="<?= htmlspecialchars($filters['check_in']); ?>">
                                <input type="hidden" name="check_out" value="<?= htmlspecialchars($filters['check_out']); ?>">
                                <input type="hidden" name="adults" value="<?= htmlspecialchars($filters['adults']); ?>">
                                <input type="hidden" name="children" value="<?= htmlspecialchars($filters['children']); ?>">
                                <input type="hidden" name="room_type_id" value="<?= (int)$option['type']['id']; ?>">
                                <input type="hidden" name="room_id" value="">
                                <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest['guest_name'] ?? ''); ?>">
                                <input type="hidden" name="guest_email" value="<?= htmlspecialchars($guest['guest_email'] ?? ''); ?>">
                                <input type="hidden" name="guest_phone" value="<?= htmlspecialchars($guest['guest_phone'] ?? ''); ?>">
                                <button class="btn btn-outline booking-submit-form" type="submit">Request this room type</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profile = document.getElementById('guestProfile');
            if (!profile) return;

            const nameInput = profile.querySelector('[name="guest_profile_name"]');
            const emailInput = profile.querySelector('[name="guest_profile_email"]');
            const phoneInput = profile.querySelector('[name="guest_profile_phone"]');

            const syncForm = (form) => {
                form.addEventListener('submit', event => {
                    const name = (nameInput.value || '').trim();
                    const email = (emailInput.value || '').trim();
                    const phone = (phoneInput.value || '').trim();

                    if (!name || (!email && !phone)) {
                        event.preventDefault();
                        alert('Please enter your name and at least one contact method before booking.');
                        return;
                    }

                    form.querySelector('input[name="guest_name"]').value = name;
                    form.querySelector('input[name="guest_email"]').value = email;
                    form.querySelector('input[name="guest_phone"]').value = phone;
                });
            };

            document.querySelectorAll('.booking-results form').forEach(syncForm);
        });
    </script>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Book Your Stay | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');
