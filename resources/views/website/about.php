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
    <section class="page-hero">
        <div class="container">
            <h1>About <?= htmlspecialchars(settings('branding.name', 'Hotela')); ?></h1>
            <p><?= htmlspecialchars($website['hero_tagline'] ?? 'Kenyan hospitality, reimagined.'); ?></p>
        </div>
    </section>
    <section class="container about-grid">
        <article>
            <h3>Our Story</h3>
            <p><?= nl2br(htmlspecialchars($website['about_content'] ?? 'Hotela combines modern comfort with timeless service.')); ?></p>
        </article>
        <article>
            <h3>Highlights</h3>
            <ul>
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
                    <li>Comfort-first rooms and thoughtful dining experiences</li>
                <?php endif; ?>
            </ul>
        </article>
    </section>
    <?php
    return ob_get_clean();
};
$pageTitle = 'About | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

