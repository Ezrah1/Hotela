<?php
$website = $website ?? settings('website', []);
$slot = function () use ($website) {
    $amenities = $website['amenities'] ?? [];
    if (is_string($amenities)) {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $amenities)));
        $amenities = array_map(function ($line) {
            [$title, $description] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            return [
                'title' => $title,
                'description' => $description ?: null,
            ];
        }, $lines);
    }
    $amenities = is_array($amenities) ? $amenities : [];
    $highlights = array_slice(array_values(array_filter(array_map(function ($item) {
        if (is_string($item)) {
            return [
                'title' => $item,
                'description' => null,
            ];
        }
        if (is_array($item) && !empty($item['title'])) {
            return [
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
            ];
        }
        return null;
    }, $amenities))), 0, 4);

    ob_start(); ?>
    <section class="page-hero page-hero-simple">
        <div class="container">
            <h1>About <?= htmlspecialchars(settings('branding.name', 'Hotela')); ?></h1>
            <p><?= htmlspecialchars($website['hero_tagline'] ?? 'Kenyan hospitality, reimagined.'); ?></p>
        </div>
    </section>
    <section class="container about-section">
        <div class="about-grid">
            <article class="about-content">
                <h2>Our Story</h2>
                <div class="about-text">
                    <?= nl2br(htmlspecialchars($website['about_content'] ?? 'Hotela combines modern comfort with timeless service.')); ?>
                </div>
            </article>
            <article class="about-highlights">
                <h2>Highlights</h2>
                <ul class="highlights-list">
                    <?php if ($highlights): ?>
                        <?php foreach ($highlights as $item): ?>
                            <li>
                                <strong><?= htmlspecialchars($item['title']); ?></strong>
                                <?php if (!empty($item['description'])): ?>
                                    <span><?= htmlspecialchars($item['description']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>
                            <strong>Comfort-first rooms</strong>
                            <span>Thoughtful dining experiences</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </article>
        </div>
    </section>
    <?php
    return ob_get_clean();
};
$pageTitle = 'About | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

