<?php
$website = $website ?? settings('website', []);
$slot = function () use ($website) {
    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>Get in Touch</h1>
            <p><?= htmlspecialchars($website['contact_message'] ?? 'We are on call 24/7 for booking support and concierge requests.'); ?></p>
        </div>
    </section>
    <section class="container contact-section">
        <div class="contact-grid">
            <article class="contact-card">
                <h2>Visit Us</h2>
                <p class="contact-address"><?= htmlspecialchars($website['contact_address'] ?? 'Nairobi, Kenya'); ?></p>
                <?php if ($map = $website['contact_map_embed'] ?? ''): ?>
                    <div class="map-embed"><?= $map; ?></div>
                <?php endif; ?>
            </article>
            <article class="contact-card">
                <h2>Say Hello</h2>
                <ul class="contact-list">
                    <li>
                        <span class="contact-label">Phone</span>
                        <a href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>"><?= htmlspecialchars(settings('branding.contact_phone', '')); ?></a>
                    </li>
                    <li>
                        <span class="contact-label">Email</span>
                        <a href="mailto:<?= htmlspecialchars(settings('branding.contact_email', '')); ?>"><?= htmlspecialchars(settings('branding.contact_email', '')); ?></a>
                    </li>
                    <?php if ($wa = $website['contact_whatsapp'] ?? ''): ?>
                        <li>
                            <a class="btn btn-primary btn-small" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};
$pageTitle = 'Contact | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

