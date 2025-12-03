<?php
$website = $website ?? settings('website', []);
$items = $items ?? [];

$slot = function () use ($items) {
    ob_start(); ?>
    <section class="page-hero">
        <div class="container">
            <h1>Gallery</h1>
            <p>Explore our beautiful spaces and stories</p>
        </div>
    </section>
    <section class="container" style="max-width: 900px; margin: 3rem auto; padding: 0 1rem;">
        <?php if (empty($items)): ?>
            <div style="text-align: center; padding: 4rem 2rem; color: #64748b;">
                <p style="font-size: 1.125rem;">No gallery items yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="gallery-posts" style="display: flex; flex-direction: column; gap: 3rem;">
                <?php foreach ($items as $item): ?>
                    <article class="gallery-post" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="gallery-post-image" style="width: 100%; aspect-ratio: 16/9; overflow: hidden; background: #f1f5f9;">
                            <img src="<?= htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?= htmlspecialchars($item['title']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </div>
                        <div class="gallery-post-content" style="padding: 2rem;">
                            <h2 style="margin: 0 0 1rem 0; font-size: 1.75rem; color: #0f172a; line-height: 1.3;">
                                <?= htmlspecialchars($item['title']); ?>
                            </h2>
                            <?php if (!empty($item['description'])): ?>
                                <div class="gallery-post-story" style="color: #475569; line-height: 1.7; font-size: 1.0625rem; white-space: pre-wrap;">
                                    <?= nl2br(htmlspecialchars($item['description'])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="gallery-post-meta" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.875rem;">
                                <?= date('F j, Y', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <style>
        .gallery-post {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .gallery-post:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .gallery-post-image img {
            transition: transform 0.3s ease;
        }
        .gallery-post:hover .gallery-post-image img {
            transform: scale(1.05);
        }
        @media (max-width: 768px) {
            .gallery-post-content {
                padding: 1.5rem !important;
            }
            .gallery-post h2 {
                font-size: 1.5rem !important;
            }
        }
    </style>
    <?php
    return ob_get_clean();
};

$pageTitle = 'Gallery | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');

