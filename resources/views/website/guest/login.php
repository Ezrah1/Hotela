<?php
$website = settings('website', []);
$slot = function () use ($redirect) {
    $error = $_GET['error'] ?? null;
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Guest Portal</h1>
            <p>Access your bookings, dining orders, and concierge notes in one place.</p>
        </div>
    </section>
    <section class="container portal-section">
        <div class="portal-auth">
        <article class="card">
            <h2>Sign in</h2>
            <p>Use your booking reference plus either the email or phone number used during reservation.</p>
            <?php if ($error === 'missing'): ?>
                <div class="alert error">Please enter both booking reference and email or phone.</div>
            <?php elseif ($error === 'invalid'): ?>
                <div class="alert error">We could not find a booking that matches those details. Double-check and try again.</div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('guest/login'); ?>" class="portal-form">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">
                <label>
                    <span>Booking reference</span>
                    <input type="text" name="reference" placeholder="e.g. HTL-1A2B3C" required>
                </label>
                <label>
                    <span>Email or phone</span>
                    <input type="text" name="identifier" placeholder="guest@example.com or +2547..." required>
                </label>
                <button class="btn btn-primary" type="submit">Access portal</button>
            </form>
        </article>
        <article class="card card-outline">
            <h3>What you’ll see</h3>
            <ul class="portal-benefits">
                <li>Upcoming & in-house bookings with live status.</li>
                <li>Food & drink orders with fulfillment updates.</li>
                <li>Concierge messages and special requests.</li>
                <li>Fast rebooking using saved preferences.</li>
            </ul>
        </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Guest Portal Login | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

