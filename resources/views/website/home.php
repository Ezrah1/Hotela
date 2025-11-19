<?php
$website = $website ?? settings('website', []);
$roomTypes = $roomTypes ?? [];
$pages = $website['pages'] ?? [];
$guestPortal = \App\Support\GuestPortal::user();
$bookingTarget = base_url('booking');
$orderTarget = base_url('order');
$bookingLink = $guestPortal ? $bookingTarget : base_url('guest/login?redirect=' . urlencode($bookingTarget));
$orderLink = $guestPortal ? $orderTarget : base_url('guest/login?redirect=' . urlencode($orderTarget));
$heroGallery = $website['hero_gallery'] ?? [];
if (is_string($heroGallery)) {
    $heroGallery = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $heroGallery)));
}
$heroGallery = array_map(function ($item) {
    if (is_string($item)) {
        return ['url' => $item, 'label' => null];
    }
    return $item;
}, $heroGallery);
if (empty($heroGallery)) {
    $heroGallery = [
        ['url' => 'https://images.unsplash.com/photo-1501117716987-c8e1ecb210cc?auto=format&fit=crop&w=1920&q=80', 'label' => 'Lobby lounge'],
        ['url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80', 'label' => 'Signature suite'],
    ];
}
$amenities = $website['amenities'] ?? [];
if (is_string($amenities)) {
    $decodedAmenities = json_decode($amenities, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAmenities)) {
        $amenities = $decodedAmenities;
    } else {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $amenities)));
        $amenities = array_map(function ($line) {
            [$title, $description] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            return [
                'title' => $title ?: 'Amenity',
                'description' => $description ?: null,
            ];
        }, $lines);
    }
}
if (empty($amenities) || !is_array($amenities)) {
    $amenities = [];
}
$amenities = array_map(function ($amenity) {
    if (is_string($amenity)) {
        $decoded = json_decode($amenity, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $amenity = $decoded;
        } else {
            [$title, $description] = array_pad(array_map('trim', explode('|', $amenity, 2)), 2, '');
            return [
                'title' => $title ?: 'Amenity',
                'description' => $description ?: null,
            ];
        }
    }

    if (!is_array($amenity)) {
        return [
            'title' => 'Amenity',
            'description' => null,
        ];
    }

    $title = $amenity['title'] ?? $amenity['label'] ?? $amenity['name'] ?? 'Amenity';
    $description = $amenity['description'] ?? $amenity['text'] ?? null;

    return [
        'title' => trim((string)$title) ?: 'Amenity',
        'description' => $description ? trim((string)$description) : null,
    ];
}, $amenities);
if (empty($amenities)) {
    $amenities = [
        ['title' => 'Wi-Fi 6', 'description' => 'Unlimited, secure, and fast.'],
        ['title' => 'Airport transfers', 'description' => 'Complimentary on 3+ night stays.'],
        ['title' => 'All-day dining', 'description' => 'Kitchen + mixology studio onsite.'],
        ['title' => '24/7 concierge', 'description' => 'Front desk and WhatsApp support.'],
    ];
}
$wa = $website['contact_whatsapp'] ?? null;
$highlights = [
    [
        'title' => trim($website['highlight_one_title'] ?? '') ?: 'Smart stays',
        'text' => trim($website['highlight_one_text'] ?? '') ?: 'Keyless arrivals & paperless folios.',
    ],
    [
        'title' => trim($website['highlight_two_title'] ?? '') ?: 'Signature Dining',
        'text' => trim($website['highlight_two_text'] ?? '') ?: 'Locally inspired menus & crafted drinks.',
    ],
    [
        'title' => trim($website['highlight_three_title'] ?? '') ?: '24/7 Support',
        'text' => trim($website['highlight_three_text'] ?? '') ?: 'Front desk and concierge on standby.',
    ],
];
$restaurantImage = $website['restaurant_image'] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80';
$heroBackgroundImage = $website['hero_background_image']
    ?? ($heroGallery[0]['url'] ?? 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80');
$brandName = settings('branding.name', 'Hotela');
$brandTagline = settings('branding.tagline', 'Integrated Hospitality OS');
$slot = function () use (
    $website,
    $pages,
    $roomTypes,
    $amenities,
    $wa,
    $restaurantImage,
    $heroBackgroundImage,
    $brandName,
    $brandTagline,
    $bookingLink,
    $orderLink,
    $highlights
) {
    ob_start(); ?>
    <section class="hero hero-simple">
        <div class="container">
            <div class="hero-simple__content">
                <?php if (!empty($heroBackgroundImage)): ?>
                    <div class="hero-simple__image">
                        <img src="<?= htmlspecialchars(asset($heroBackgroundImage)); ?>" alt="<?= htmlspecialchars($brandName); ?>">
                    </div>
                <?php endif; ?>
                <div class="hero-simple__text">
                    <h1><?= htmlspecialchars($website['hero_heading'] ?? $brandName); ?></h1>
                    <p class="hero-simple__tagline"><?= htmlspecialchars($website['hero_tagline'] ?? $brandTagline); ?></p>
                    <p class="hero-simple__summary"><?= htmlspecialchars($website['promo_message'] ?? 'Thoughtfully curated spaces with seamless digital touchpoints.'); ?></p>
                    <div class="hero-simple__actions">
                        <a class="btn btn-primary" href="<?= $bookingLink; ?>"><?= htmlspecialchars($website['hero_cta_text'] ?? 'Book a Stay'); ?></a>
                        <?php if (!empty($pages['rooms'])): ?>
                            <a class="btn btn-outline" href="<?= base_url('rooms'); ?>">View Rooms</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="intro-section">
        <div class="container intro-grid">
            <article>
                <p class="eyebrow"><?= htmlspecialchars($website['intro_tagline'] ?? 'Welcome'); ?></p>
                <h2><?= htmlspecialchars($website['intro_heading'] ?? 'A modern, warm, Kenyan hotel experience.'); ?></h2>
                <p><?= nl2br(htmlspecialchars($website['intro_copy'] ?? 'Every suite, plate, and playlist is curated from the Hotela command center, so your stay feels effortless.')); ?></p>
            </article>
            <article class="intro-card">
                <ul>
                    <li>Check-in <?= htmlspecialchars(settings('hotel.check_in_time', '14:00')); ?></li>
                    <li>Check-out <?= htmlspecialchars(settings('hotel.check_out_time', '10:00')); ?></li>
                    <?php if (!empty($website['booking_enabled'])): ?>
                        <li>Instant confirmation via email + SMS</li>
                    <?php endif; ?>
                    <?php if (!empty($website['order_enabled'])): ?>
                        <li>Room service & pickup ordering online</li>
                    <?php endif; ?>
                </ul>
                <a class="btn btn-outline btn-small" href="<?= base_url('about'); ?>">Discover the story</a>
            </article>
        </div>
    </section>
    <section class="feature-section" id="rooms">
        <div class="container">
            <div class="section-heading text-center">
                <h2>Our Rooms</h2>
                <p><?= htmlspecialchars($website['rooms_intro'] ?? 'Comfort-forward suites fitted for rest, work, and play.'); ?></p>
            </div>
            <div class="feature-grid">
                <?php foreach ($roomTypes as $type): ?>
                    <?php
                    $photo = $type['image'] ?? null;
                    if ($photo) {
                        $photo = asset($photo);
                    } else {
                        $photo = 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80';
                    }
                    $roomAmenities = [];
                    if (!empty($type['amenities'])) {
                        $decoded = is_array($type['amenities']) ? $type['amenities'] : json_decode($type['amenities'], true);
                        if (is_array($decoded)) {
                            $roomAmenities = array_slice($decoded, 0, 3);
                        }
                    }
                    ?>
                    <article class="feature-card">
                        <div class="feature-card__media" style="background-image: url('<?= htmlspecialchars($photo); ?>');"></div>
                        <div class="feature-card__body">
                            <div class="feature-card__head">
                                <h4><?= htmlspecialchars($type['name'] ?? 'Room Type'); ?></h4>
                            </div>
                            <p><?= htmlspecialchars($type['description'] ?? 'Spacious interiors, premium linens, and curated amenities.'); ?></p>
                            <?php if (!empty($type['base_rate'])): ?>
                                <p class="rate">From KES <?= number_format($type['base_rate']); ?> / night</p>
                            <?php endif; ?>
                            <?php if ($roomAmenities): ?>
                                <ul class="room-amenities">
                                    <?php foreach ($roomAmenities as $amenity): ?>
                                        <li><?= htmlspecialchars(is_string($amenity) ? $amenity : ($amenity['label'] ?? 'Amenity')); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <a class="btn btn-outline btn-small" href="<?= base_url('rooms?type=' . (int)$type['id'] . '#rooms'); ?>">View Room</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (empty($roomTypes)): ?>
                <p class="empty-state">Room types will appear here once added from the admin dashboard.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php if (!empty($pages['food'])): ?>
    <section class="restaurant-highlight">
        <div class="container highlight-card">
            <div>
                <p class="eyebrow"><?= htmlspecialchars($website['restaurant_tagline'] ?? 'Restaurant & Bar'); ?></p>
                <h3><?= htmlspecialchars($website['restaurant_title'] ?? 'Sunrise breakfast to cocktail hour.'); ?></h3>
                <p><?= htmlspecialchars($website['food_intro'] ?? 'From sunrise breakfasts to late-night cocktails.'); ?></p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-small" href="<?= base_url('drinks-food'); ?>"><?= htmlspecialchars($website['restaurant_cta_text'] ?? 'View Menu'); ?></a>
                    <?php if (!empty($website['order_enabled']) && !empty($pages['order'])): ?>
                        <a class="btn btn-outline btn-small" href="<?= $orderLink; ?>">Order Online</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="highlight-media" style="background-image: url('<?= htmlspecialchars($restaurantImage); ?>');"></div>
        </div>
    </section>
    <?php endif; ?>
    <section class="amenities-section">
        <div class="container">
            <div class="section-heading">
                <h2>Amenities designed for modern travelers</h2>
                <p>Everything you expect, plus thoughtful extras powered by Hotela.</p>
            </div>
            <div class="amenities-grid">
                <?php foreach ($amenities as $amenity): ?>
                    <article class="amenity-card">
                        <h4><?= htmlspecialchars($amenity['title']); ?></h4>
                        <?php if (!empty($amenity['description'])): ?>
                            <p><?= htmlspecialchars($amenity['description']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <section class="cta-section">
        <div class="container">
            <h3>Planning a visit?</h3>
            <p><?= htmlspecialchars($website['contact_message'] ?? 'Call or message our team for bespoke itineraries and offers.'); ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= $bookingLink; ?>">Reserve a Room</a>
                <a class="btn btn-outline" href="<?= base_url('contact'); ?>">Contact Us</a>
                <?php if ($wa): ?>
                    <a class="btn btn-whatsapp" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" rel="noopener">WhatsApp Concierge</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$pageTitle = $website['meta_title'] ?? 'Welcome';
include view_path('layouts/public.php');
