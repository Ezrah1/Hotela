<?php
ob_start();
?>
<div>
    <h1 class="guest-page-title">Welcome back, <?= htmlspecialchars($guest['guest_name'] ?? 'Guest'); ?></h1>
    <p class="guest-page-subtitle">Quick access to your stays and orders</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        <!-- Book a Room Card -->
        <a href="<?= base_url('booking'); ?>" class="guest-card" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
            <div style="text-align: center; padding: 1rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🏨</div>
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">Book a Room</h2>
                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                    Reserve your stay with us
                </p>
            </div>
        </a>

        <!-- Order Food Card -->
        <a href="<?= base_url('order'); ?>" class="guest-card" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
            <div style="text-align: center; padding: 1rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🍽️</div>
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">Order Food</h2>
                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                    Browse our menu & order
                </p>
            </div>
        </a>

        <!-- Upcoming Bookings Card -->
        <a href="<?= base_url('guest/upcoming-bookings'); ?>" class="guest-card" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
            <div style="text-align: center; padding: 1rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">Upcoming Bookings</h2>
                <p style="font-size: 2rem; font-weight: 700; color: var(--guest-primary); margin-bottom: 0.5rem;">
                    <?= $upcomingCount; ?>
                </p>
                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                    View your upcoming stays
                </p>
            </div>
        </a>

        <!-- Active Orders Card -->
        <a href="<?= base_url('guest/active-orders'); ?>" class="guest-card" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
            <div style="text-align: center; padding: 1rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🍽️</div>
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">Active Orders</h2>
                <p style="font-size: 2rem; font-weight: 700; color: var(--guest-primary); margin-bottom: 0.5rem;">
                    <?= $activeOrdersCount; ?>
                </p>
                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                    Track your food & drink orders
                </p>
            </div>
        </a>

        <!-- Past Bookings Card -->
        <a href="<?= base_url('guest/past-bookings'); ?>" class="guest-card" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
            <div style="text-align: center; padding: 1rem 0;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--guest-text);">Past Bookings</h2>
                <p style="font-size: 2rem; font-weight: 700; color: var(--guest-primary); margin-bottom: 0.5rem;">
                    <?= $pastCount; ?>
                </p>
                <p style="color: var(--guest-text-light); font-size: 0.9rem;">
                    View your booking history
                </p>
            </div>
        </a>
    </div>

    <style>
        .guest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</div>

<?php
$slot = ob_get_clean();
include view_path('layouts/guest.php');
?>
