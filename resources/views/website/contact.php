<?php
$website = $website ?? settings('website', []);
$slot = function () use ($website) {
    ob_start(); ?>
    <section class="page-hero">
        <div class="container">
            <h1>Get in Touch</h1>
            <p><?= htmlspecialchars($website['contact_message'] ?? 'We are on call 24/7 for booking support and concierge requests.'); ?></p>
        </div>
    </section>
    <section class="container contact-grid">
        <article>
            <h3>Visit Us</h3>
            <p><?= htmlspecialchars($website['contact_address'] ?? 'Nairobi, Kenya'); ?></p>
            <?php if ($map = $website['contact_map_embed'] ?? ''): ?>
                <div class="map-embed"><?= $map; ?></div>
            <?php endif; ?>
        </article>
        <article>
            <h3>Say Hello</h3>
            <ul class="contact-list">
                <li>Phone: <a href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>"><?= htmlspecialchars(settings('branding.contact_phone', '')); ?></a></li>
                <li>Email: <a href="mailto:<?= htmlspecialchars(settings('branding.contact_email', '')); ?>"><?= htmlspecialchars(settings('branding.contact_email', '')); ?></a></li>
                <?php if ($wa = $website['contact_whatsapp'] ?? ''): ?>
                    <li><a class="btn btn-primary btn-small" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
                <?php endif; ?>
            </ul>
        </article>
    </section>
    <?php
    return ob_get_clean();
};
$pageTitle = 'Contact | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

