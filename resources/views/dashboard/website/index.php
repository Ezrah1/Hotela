<?php
$pageTitle = 'Website Management | Hotela';
$website = $website ?? [];
$branding = $branding ?? [];

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="website-header">
        <div>
            <h2>Website Content Management</h2>
            <p class="website-subtitle">Manage your public-facing website content, pages, and settings.</p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url(); ?>" target="_blank" class="btn btn-outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                View Website
            </a>
        </div>
    </header>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="website-tabs">
        <button class="tab active" data-tab="homepage">Homepage</button>
        <button class="tab" data-tab="pages">Pages</button>
        <button class="tab" data-tab="content">Content</button>
        <button class="tab" data-tab="contact">Contact</button>
        <button class="tab" data-tab="seo">SEO</button>
    </div>

    <form method="post" action="<?= base_url('staff/dashboard/website/update'); ?>" class="website-form">
        <input type="hidden" name="group" value="website">

        <!-- Homepage Tab -->
        <div class="tab-content active" data-content="homepage">
            <h3>Homepage Settings</h3>
            
            <div class="form-section">
                <h4>Hero Section</h4>
                <div class="form-group">
                    <label>
                        <span>Hero Heading</span>
                        <input type="text" name="hero_heading" value="<?= htmlspecialchars($website['hero_heading'] ?? 'Welcome to Hotela'); ?>" placeholder="Welcome to Our Hotel">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Hero Tagline</span>
                        <input type="text" name="hero_tagline" value="<?= htmlspecialchars($website['hero_tagline'] ?? ''); ?>" placeholder="Your perfect getaway">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Call-to-Action Button Text</span>
                        <input type="text" name="hero_cta_text" value="<?= htmlspecialchars($website['hero_cta_text'] ?? 'Book Now'); ?>" placeholder="Book Now">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Call-to-Action Button Link</span>
                        <input type="text" name="hero_cta_link" value="<?= htmlspecialchars($website['hero_cta_link'] ?? '/booking'); ?>" placeholder="/booking">
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h4>Highlights</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <span>Highlight #1 Title</span>
                            <input type="text" name="highlight_one_title" value="<?= htmlspecialchars($website['highlight_one_title'] ?? ''); ?>">
                        </label>
                        <label>
                            <span>Highlight #1 Text</span>
                            <textarea name="highlight_one_text" rows="3"><?= htmlspecialchars($website['highlight_one_text'] ?? ''); ?></textarea>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Highlight #2 Title</span>
                            <input type="text" name="highlight_two_title" value="<?= htmlspecialchars($website['highlight_two_title'] ?? ''); ?>">
                        </label>
                        <label>
                            <span>Highlight #2 Text</span>
                            <textarea name="highlight_two_text" rows="3"><?= htmlspecialchars($website['highlight_two_text'] ?? ''); ?></textarea>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Highlight #3 Title</span>
                            <input type="text" name="highlight_three_title" value="<?= htmlspecialchars($website['highlight_three_title'] ?? ''); ?>">
                        </label>
                        <label>
                            <span>Highlight #3 Text</span>
                            <textarea name="highlight_three_text" rows="3"><?= htmlspecialchars($website['highlight_three_text'] ?? ''); ?></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>Banner</h4>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="banner_enabled" value="0">
                        <input type="checkbox" name="banner_enabled" value="1" <?= !empty($website['banner_enabled']) ? 'checked' : ''; ?>>
                        <span>Show banner on homepage</span>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Banner Text</span>
                        <input type="text" name="banner_text" value="<?= htmlspecialchars($website['banner_text'] ?? ''); ?>" placeholder="Special offer announcement">
                    </label>
                </div>
            </div>
        </div>

        <!-- Pages Tab -->
        <div class="tab-content" data-content="pages">
            <h3>Page Visibility</h3>
            
            <div class="form-section">
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="pages[rooms]" value="0">
                        <input type="checkbox" name="pages[rooms]" value="1" <?= !empty($website['pages']['rooms'] ?? true) ? 'checked' : ''; ?>>
                        <span>Show Rooms page</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="pages[food]" value="0">
                        <input type="checkbox" name="pages[food]" value="1" <?= !empty($website['pages']['food'] ?? true) ? 'checked' : ''; ?>>
                        <span>Show Drinks & Food page</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="pages[about]" value="0">
                        <input type="checkbox" name="pages[about]" value="1" <?= !empty($website['pages']['about'] ?? true) ? 'checked' : ''; ?>>
                        <span>Show About page</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="pages[contact]" value="0">
                        <input type="checkbox" name="pages[contact]" value="1" <?= !empty($website['pages']['contact'] ?? true) ? 'checked' : ''; ?>>
                        <span>Show Contact page</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="pages[order]" value="0">
                        <input type="checkbox" name="pages[order]" value="1" <?= !empty($website['pages']['order'] ?? true) ? 'checked' : ''; ?>>
                        <span>Show Order/Booking page</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h4>Features</h4>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="booking_enabled" value="0">
                        <input type="checkbox" name="booking_enabled" value="1" <?= !empty($website['booking_enabled'] ?? true) ? 'checked' : ''; ?>>
                        <span>Enable booking engine</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="hidden" name="order_enabled" value="0">
                        <input type="checkbox" name="order_enabled" value="1" <?= !empty($website['order_enabled'] ?? true) ? 'checked' : ''; ?>>
                        <span>Enable food ordering</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Content Tab -->
        <div class="tab-content" data-content="content">
            <h3>Page Content</h3>
            
            <div class="form-section">
                <div class="form-group">
                    <label>
                        <span>Rooms Page Introduction</span>
                        <textarea name="rooms_intro" rows="4" placeholder="Welcome to our rooms..."><?= htmlspecialchars($website['rooms_intro'] ?? ''); ?></textarea>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Food & Drinks Page Introduction</span>
                        <textarea name="food_intro" rows="4" placeholder="Discover our menu..."><?= htmlspecialchars($website['food_intro'] ?? ''); ?></textarea>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>About Page Content</span>
                        <textarea name="about_content" rows="6" placeholder="About our hotel..."><?= htmlspecialchars($website['about_content'] ?? ''); ?></textarea>
                    </label>
                </div>
            </div>
        </div>

        <!-- Contact Tab -->
        <div class="tab-content" data-content="contact">
            <h3>Contact Information</h3>
            
            <div class="form-section">
                <div class="form-group">
                    <label>
                        <span>Address</span>
                        <input type="text" name="contact_address" value="<?= htmlspecialchars($website['contact_address'] ?? ''); ?>" placeholder="123 Main Street, City, Country">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>WhatsApp Number</span>
                        <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($website['contact_whatsapp'] ?? ''); ?>" placeholder="+254700000000">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Contact Message</span>
                        <textarea name="contact_message" rows="4" placeholder="Get in touch with us..."><?= htmlspecialchars($website['contact_message'] ?? ''); ?></textarea>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Map Embed (iframe or link)</span>
                        <textarea name="contact_map_embed" rows="4" placeholder="<iframe src='...'></iframe>"><?= htmlspecialchars($website['contact_map_embed'] ?? ''); ?></textarea>
                        <small>Paste Google Maps embed code or link</small>
                    </label>
                </div>
            </div>
        </div>

        <!-- SEO Tab -->
        <div class="tab-content" data-content="seo">
            <h3>SEO Settings</h3>
            
            <div class="form-section">
                <div class="form-group">
                    <label>
                        <span>Meta Title</span>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($website['meta_title'] ?? ''); ?>" placeholder="Hotela - Your Perfect Stay">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Meta Description</span>
                        <textarea name="meta_description" rows="3" placeholder="Experience luxury and comfort..."><?= htmlspecialchars($website['meta_description'] ?? ''); ?></textarea>
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Meta Keywords</span>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($website['meta_keywords'] ?? ''); ?>" placeholder="hotel, accommodation, booking">
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.website-tabs .tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.querySelector(`[data-content="${targetTab}"]`).classList.add('active');
        });
    });
});
</script>

<style>
.website-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.website-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.website-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.website-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    flex-wrap: wrap;
}

.website-tabs .tab {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.website-tabs .tab:hover {
    color: #475569;
}

.website-tabs .tab.active {
    color: #8b5cf6;
    border-bottom-color: #8b5cf6;
}

.website-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-content h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.form-section h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #475569;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label span {
    font-weight: 600;
    color: #475569;
    font-size: 0.95rem;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #1e293b;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.form-group small {
    color: #64748b;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.checkbox {
    flex-direction: row;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-actions {
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.75rem;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .website-header {
        flex-direction: column;
    }

    .website-tabs {
        overflow-x: auto;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

