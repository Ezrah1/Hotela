<?php
$brandName = settings('branding.name', 'Hotela');
$logoPath = settings('branding.logo', 'assets/img/hotela-logo.svg');
$website = settings('website', []);
$guestPortal = \App\Support\GuestPortal::user();
$primaryColor = $website['primary_color'] ?? '#0d9488';
$secondaryColor = $website['secondary_color'] ?? '#0f172a';
$accentColor = $website['accent_color'] ?? '#f97316';
$pages = $website['pages'] ?? [];
$metaTitle = $website['meta_title'] ?? ($pageTitle ?? $brandName);
$metaDescription = $website['meta_description'] ?? '';
$metaKeywords = $website['meta_keywords'] ?? '';
$bookingEnabled = !empty($website['booking_enabled']);
$orderEnabled = !empty($website['order_enabled']);
$bookingPath = base_url('booking');
$orderPath = base_url('order');
$bookingLink = $guestPortal ? $bookingPath : base_url('guest/login?redirect=' . urlencode($bookingPath));
$orderLink = $guestPortal ? $orderPath : base_url('guest/login?redirect=' . urlencode($orderPath));
$socialLinks = $website['social_links'] ?? [];
if (is_string($socialLinks)) {
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $socialLinks)));
    $normalized = [];
    foreach ($lines as $line) {
        [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if ($label && $url) {
            $normalized[$label] = $url;
        }
    }
    $socialLinks = $normalized;
}
$footerLinks = $website['footer_links'] ?? [];
if (is_string($footerLinks)) {
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $footerLinks)));
    $footerLinks = array_map(function ($line) {
        [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if (!$label || !$url) {
            return null;
        }
        return ['label' => $label, 'url' => $url];
    }, $lines);
    $footerLinks = array_values(array_filter($footerLinks));
}
$poweredByEnabled = ($website['powered_by_hotela'] ?? true) !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords); ?>">
    <title><?= htmlspecialchars($metaTitle); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg'); ?>" type="image/svg+xml">
    <!-- Font Awesome (icons for category fallbacks on menu) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/J6MdZ0OZbG8WsK3p1w1j6MZ9p3G5w5qUO5l5d9zF5lY3hQbq9i3o7Ytqz6v0xG4l7+Dz3u6Qw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --primary: <?= htmlspecialchars($primaryColor); ?>;
            --dark: <?= htmlspecialchars($secondaryColor); ?>;
            --accent: <?= htmlspecialchars($accentColor); ?>;
        }
    </style>
</head>
<body class="public-body">
<header class="site-header public-header" data-sticky>
    <div class="container">
        <a class="brand" href="<?= base_url('/'); ?>" aria-label="<?= htmlspecialchars($brandName); ?>">
            <img src="<?= asset($logoPath); ?>" alt="<?= htmlspecialchars($brandName); ?> logo">
        </a>
        <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle navigation">
        <label for="nav-toggle" class="nav-toggle-label" aria-label="Toggle navigation">
            <span></span>
        </label>
        <nav class="nav public-nav">
            <a href="<?= base_url('/'); ?>">Home</a>
            <?php if (!empty($pages['rooms'])): ?>
                <a href="<?= base_url('rooms'); ?>">Rooms</a>
            <?php endif; ?>
            <?php if (!empty($pages['food'])): ?>
                <a href="<?= base_url('drinks-food'); ?>">Drinks & Food</a>
            <?php endif; ?>
            <?php if (!empty($pages['about'])): ?>
                <a href="<?= base_url('about'); ?>">About</a>
            <?php endif; ?>
            <?php if (!empty($pages['contact'])): ?>
                <a href="<?= base_url('contact'); ?>">Contact</a>
            <?php endif; ?>
            <?php if ($bookingEnabled): ?>
                <a class="btn btn-primary btn-small highlight" href="<?= $bookingLink; ?>">Book Now</a>
            <?php endif; ?>
            <?php
            if ($guestPortal) {
                $guestName = $guestPortal['guest_name'] ?? '';
                $firstName = $guestName ? explode(' ', trim($guestName))[0] : 'Guest';
                ?>
                <a class="btn btn-ghost btn-small" href="<?= base_url('guest/portal'); ?>">
                    <?= htmlspecialchars("Hi, {$firstName}"); ?>
                </a>
            <?php } else { ?>
                <a class="btn btn-ghost btn-small" href="<?= base_url('guest/login'); ?>">My Account</a>
            <?php } ?>
        </nav>
    </div>
</header>
<main class="public-main">
    <?php
    if (is_callable($slot ?? null)) {
        echo ($slot)();
    } else {
        echo $slot ?? '';
    }
    ?>
</main>
<footer class="public-footer">
    <div class="container footer-grid">
        <div>
            <strong><?= htmlspecialchars($brandName); ?></strong>
            <p><?= htmlspecialchars(settings('website.contact_address', '')); ?></p>
            <p>&copy; <?= date('Y'); ?> <?= htmlspecialchars($brandName); ?><?php if ($poweredByEnabled): ?> · Powered by Hotela<?php endif; ?></p>
        </div>
        <div class="footer-links">
            <span>Quick links</span>
            <ul>
                <?php if (!empty($pages['rooms'])): ?>
                    <li><a href="<?= base_url('rooms'); ?>">Rooms</a></li>
                <?php endif; ?>
                <?php if (!empty($pages['contact'])): ?>
                    <li><a href="<?= base_url('contact'); ?>">Contact</a></li>
                <?php endif; ?>
                <?php if (!empty($pages['order'])): ?>
                    <li><a href="<?= $orderLink; ?>">Order | Booking</a></li>
                <?php endif; ?>
                <?php if ($footerLinks): ?>
                    <?php foreach ($footerLinks as $link): ?>
                        <li><a href="<?= htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener"><?= htmlspecialchars($link['label']); ?></a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-contact">
            <p>Phone: <a href="tel:<?= htmlspecialchars(settings('branding.contact_phone', '')); ?>"><?= htmlspecialchars(settings('branding.contact_phone', '')); ?></a></p>
            <p>Email: <a href="mailto:<?= htmlspecialchars(settings('branding.contact_email', '')); ?>"><?= htmlspecialchars(settings('branding.contact_email', '')); ?></a></p>
            <?php if ($wa = settings('website.contact_whatsapp')): ?>
                <p><a class="btn btn-outline btn-small" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" rel="noopener">WhatsApp Us</a></p>
            <?php endif; ?>
            <?php if ($socialLinks): ?>
                <div class="social-links">
                    <?php foreach ($socialLinks as $label => $url): ?>
                        <a href="<?= htmlspecialchars($url); ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars($label); ?>"><?= htmlspecialchars($label); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="footer-policies">
            <span>Policies</span>
            <ul>
                <li><a href="<?= base_url('privacy'); ?>">Privacy Policy</a></li>
                <li><a href="<?= base_url('terms'); ?>">Terms & Conditions</a></li>
                <li><a href="<?= base_url('cancellation'); ?>">Cancellation Policy</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-meta">
        <span>&copy; <?= date('Y'); ?> <?= htmlspecialchars($brandName); ?>. All rights reserved.</span>
        <?php if ($poweredByEnabled): ?>
            <span>Powered by Hotela</span>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>

