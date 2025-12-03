<?php
$website = settings('website', []);
$slot = function () use ($website) {
    ob_start(); ?>
    <section class="page-hero">
        <div class="container">
            <h1>Conferencing</h1>
            <p>Professional meeting spaces for your business needs</p>
        </div>
    </section>
    <section class="container">
        <div class="content-section">
            <h2>Conference Facilities</h2>
            <p>Our state-of-the-art conference rooms are designed to meet all your business requirements. Whether you're hosting a small team meeting or a large corporate event, we have the perfect space for you.</p>
            
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0;">
                <div>
                    <h3>Meeting Rooms</h3>
                    <p>Fully equipped meeting rooms with modern AV equipment, high-speed internet, and comfortable seating.</p>
                </div>
                <div>
                    <h3>Conference Halls</h3>
                    <p>Spacious halls perfect for large gatherings, presentations, and corporate events.</p>
                </div>
                <div>
                    <h3>Business Center</h3>
                    <p>Access to printing, copying, and other business services to support your meeting needs.</p>
                </div>
            </div>
            
            <div style="margin-top: 3rem; padding: 2rem; background: #f8fafc; border-radius: 8px;">
                <h3>Contact Us</h3>
                <p>For booking inquiries and more information about our conferencing facilities, please contact us:</p>
                <p><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>"><?= htmlspecialchars(settings('branding.contact_phone', '')); ?></a></p>
                <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars(settings('branding.contact_email', '')); ?>"><?= htmlspecialchars(settings('branding.contact_email', '')); ?></a></p>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Conferencing | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

