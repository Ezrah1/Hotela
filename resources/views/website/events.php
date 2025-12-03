<?php
$website = settings('website', []);
$slot = function () use ($website) {
    ob_start(); ?>
    <section class="page-hero">
        <div class="container">
            <h1>Events</h1>
            <p>Host memorable events in our elegant spaces</p>
        </div>
    </section>
    <section class="container">
        <div class="content-section">
            <h2>Event Hosting</h2>
            <p>Create unforgettable experiences with our versatile event spaces. From intimate celebrations to grand gatherings, we provide the perfect setting for your special occasions.</p>
            
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0;">
                <div>
                    <h3>Weddings</h3>
                    <p>Beautiful venues for your special day, with customizable packages to make your wedding unforgettable.</p>
                </div>
                <div>
                    <h3>Corporate Events</h3>
                    <p>Professional spaces for conferences, seminars, product launches, and team building activities.</p>
                </div>
                <div>
                    <h3>Social Gatherings</h3>
                    <p>Perfect settings for birthdays, anniversaries, graduations, and other milestone celebrations.</p>
                </div>
            </div>
            
            <div style="margin-top: 3rem; padding: 2rem; background: #f8fafc; border-radius: 8px;">
                <h3>Plan Your Event</h3>
                <p>Our events team is ready to help you plan and execute your perfect event. Contact us to discuss your requirements:</p>
                <p><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>"><?= htmlspecialchars(settings('branding.contact_phone', '')); ?></a></p>
                <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars(settings('branding.contact_email', '')); ?>"><?= htmlspecialchars(settings('branding.contact_email', '')); ?></a></p>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Events | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

