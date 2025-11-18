<?php
$website = $settings['website'] ?? [];
function checked($value) { return !empty($value) ? 'checked' : ''; }
?>
<h4>Brand Controls</h4>
<label>
    <span>Primary Color</span>
    <input type="color" name="primary_color" value="<?= htmlspecialchars($website['primary_color'] ?? '#0d9488'); ?>">
</label>
<label>
    <span>Secondary Color</span>
    <input type="color" name="secondary_color" value="<?= htmlspecialchars($website['secondary_color'] ?? '#0f172a'); ?>">
</label>
<label>
    <span>Tagline</span>
    <input type="text" name="hero_tagline" value="<?= htmlspecialchars($website['hero_tagline'] ?? ''); ?>">
</label>
<label>
    <span>Hero Heading</span>
    <input type="text" name="hero_heading" value="<?= htmlspecialchars($website['hero_heading'] ?? ''); ?>">
</label>
<label>
    <span>Hero CTA Text</span>
    <input type="text" name="hero_cta_text" value="<?= htmlspecialchars($website['hero_cta_text'] ?? ''); ?>">
</label>
<label>
    <span>Hero CTA Link</span>
    <input type="text" name="hero_cta_link" value="<?= htmlspecialchars($website['hero_cta_link'] ?? '/booking'); ?>">
</label>

<h4>Highlights</h4>
<label>
    <span>Highlight #1 Title</span>
    <input type="text" name="highlight_one_title" value="<?= htmlspecialchars($website['highlight_one_title'] ?? ''); ?>">
</label>
<label>
    <span>Highlight #1 Text</span>
    <input type="text" name="highlight_one_text" value="<?= htmlspecialchars($website['highlight_one_text'] ?? ''); ?>">
</label>
<label>
    <span>Highlight #2 Title</span>
    <input type="text" name="highlight_two_title" value="<?= htmlspecialchars($website['highlight_two_title'] ?? ''); ?>">
</label>
<label>
    <span>Highlight #2 Text</span>
    <input type="text" name="highlight_two_text" value="<?= htmlspecialchars($website['highlight_two_text'] ?? ''); ?>">
</label>
<label>
    <span>Highlight #3 Title</span>
    <input type="text" name="highlight_three_title" value="<?= htmlspecialchars($website['highlight_three_title'] ?? ''); ?>">
</label>
<label>
    <span>Highlight #3 Text</span>
    <input type="text" name="highlight_three_text" value="<?= htmlspecialchars($website['highlight_three_text'] ?? ''); ?>">
</label>

<h4>Homepage Banner</h4>
<label class="checkbox">
    <input type="hidden" name="banner_enabled" value="0">
    <input type="checkbox" name="banner_enabled" value="1" <?= checked($website['banner_enabled'] ?? false); ?>>
    <span>Show banner on homepage</span>
</label>
<label>
    <span>Banner Text</span>
    <input type="text" name="banner_text" value="<?= htmlspecialchars($website['banner_text'] ?? ''); ?>">
</label>

<h4>Pages & Toggles</h4>
<?php
$pages = $website['pages'] ?? [];
?>
<label class="checkbox">
    <input type="hidden" name="pages[rooms]" value="0">
    <input type="checkbox" name="pages[rooms]" value="1" <?= checked($pages['rooms'] ?? true); ?>>
    <span>Show Rooms page</span>
</label>
<label class="checkbox">
    <input type="hidden" name="pages[food]" value="0">
    <input type="checkbox" name="pages[food]" value="1" <?= checked($pages['food'] ?? true); ?>>
    <span>Show Drinks & Food page</span>
</label>
<label class="checkbox">
    <input type="hidden" name="pages[about]" value="0">
    <input type="checkbox" name="pages[about]" value="1" <?= checked($pages['about'] ?? true); ?>>
    <span>Show About page</span>
</label>
<label class="checkbox">
    <input type="hidden" name="pages[contact]" value="0">
    <input type="checkbox" name="pages[contact]" value="1" <?= checked($pages['contact'] ?? true); ?>>
    <span>Show Contact page</span>
</label>
<label class="checkbox">
    <input type="hidden" name="pages[order]" value="0">
    <input type="checkbox" name="pages[order]" value="1" <?= checked($pages['order'] ?? true); ?>>
    <span>Show Order | Booking page</span>
</label>
<label class="checkbox">
    <input type="hidden" name="booking_enabled" value="0">
    <input type="checkbox" name="booking_enabled" value="1" <?= checked($website['booking_enabled'] ?? true); ?>>
    <span>Enable booking engine</span>
</label>
<label class="checkbox">
    <input type="hidden" name="order_enabled" value="0">
    <input type="checkbox" name="order_enabled" value="1" <?= checked($website['order_enabled'] ?? true); ?>>
    <span>Enable food ordering CTA</span>
</label>

<label>
    <span>Rooms Intro Text</span>
    <textarea name="rooms_intro"><?= htmlspecialchars($website['rooms_intro'] ?? ''); ?></textarea>
</label>
<label>
    <span>Food Intro Text</span>
    <textarea name="food_intro"><?= htmlspecialchars($website['food_intro'] ?? ''); ?></textarea>
</label>
<label>
    <span>About Content</span>
    <textarea name="about_content"><?= htmlspecialchars($website['about_content'] ?? ''); ?></textarea>
</label>
<label>
    <span>Contact Message</span>
    <textarea name="contact_message"><?= htmlspecialchars($website['contact_message'] ?? ''); ?></textarea>
</label>

<h4>Contact Details</h4>
<label>
    <span>Address</span>
    <input type="text" name="contact_address" value="<?= htmlspecialchars($website['contact_address'] ?? ''); ?>">
</label>
<label>
    <span>WhatsApp Number</span>
    <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($website['contact_whatsapp'] ?? ''); ?>">
</label>
<label>
    <span>Map Embed (iframe or link)</span>
    <textarea name="contact_map_embed"><?= htmlspecialchars($website['contact_map_embed'] ?? ''); ?></textarea>
</label>

<h4>SEO</h4>
<label>
    <span>Meta Title</span>
    <input type="text" name="meta_title" value="<?= htmlspecialchars($website['meta_title'] ?? ''); ?>">
</label>
<label>
    <span>Meta Description</span>
    <textarea name="meta_description"><?= htmlspecialchars($website['meta_description'] ?? ''); ?></textarea>
</label>
<label>
    <span>Meta Keywords</span>
    <input type="text" name="meta_keywords" value="<?= htmlspecialchars($website['meta_keywords'] ?? ''); ?>">
</label>

<label>
    <span>Room Display Mode</span>
    <select name="room_display_mode">
        <?php
        $mode = $website['room_display_mode'] ?? 'both';
        ?>
        <option value="name" <?= $mode === 'name' ? 'selected' : ''; ?>>Room names only</option>
        <option value="type" <?= $mode === 'type' ? 'selected' : ''; ?>>Room types only</option>
        <option value="both" <?= $mode === 'both' ? 'selected' : ''; ?>>Names + Types</option>
    </select>
</label>
