<?php
$website = $settings['website'] ?? [];
$branding = $settings['branding'] ?? [];
function checked($value) { return !empty($value) ? 'checked' : ''; }
?>
<div class="website-settings-wrapper">
    <div class="website-header">
        <div>
            <h4>Website Content Management</h4>
            <p class="website-subtitle">Manage your public-facing website content, pages, and settings.</p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url(); ?>" target="_blank" class="btn btn-outline btn-small">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                View Website
            </a>
        </div>
    </div>

    <div class="website-tabs">
        <button type="button" class="tab active" data-tab="branding">Branding</button>
        <button type="button" class="tab" data-tab="homepage">Homepage</button>
        <button type="button" class="tab" data-tab="pages">Pages</button>
        <button type="button" class="tab" data-tab="content">Content</button>
        <button type="button" class="tab" data-tab="contact">Contact</button>
        <button type="button" class="tab" data-tab="seo">SEO</button>
    </div>

    <!-- Branding Tab -->
    <div class="tab-content active" data-content="branding">
        <h3>Brand Controls</h3>
        
        <div class="form-section">
            <h4>Color Scheme</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>
                        <span>Primary Color</span>
                        <input type="color" name="primary_color" value="<?= htmlspecialchars($website['primary_color'] ?? '#0d9488'); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Secondary Color</span>
                        <input type="color" name="secondary_color" value="<?= htmlspecialchars($website['secondary_color'] ?? '#0f172a'); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <span>Accent Color</span>
                        <input type="color" name="accent_color" value="<?= htmlspecialchars($website['accent_color'] ?? '#f97316'); ?>">
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Homepage Tab -->
    <div class="tab-content" data-content="homepage">
        <h3>Homepage Settings</h3>
        
        <div class="form-section">
            <h4>Hero Section</h4>
            <div class="form-group">
                <label>
                    <span>Hero Heading</span>
                    <input type="text" name="hero_heading" value="<?= htmlspecialchars($website['hero_heading'] ?? ''); ?>" placeholder="Welcome to Our Hotel">
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
                    <span>Promo Message</span>
                    <textarea name="promo_message" rows="3" placeholder="Thoughtfully curated spaces with seamless digital touchpoints."><?= htmlspecialchars($website['promo_message'] ?? ''); ?></textarea>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Hero Background Image</span>
                    <div class="image-upload-field">
                        <input type="file" id="hero_background_image_input" accept="image/*" style="display: none;">
                        <input type="hidden" name="hero_background_image" id="hero_background_image_url" value="<?= htmlspecialchars($website['hero_background_image'] ?? ''); ?>">
                        <div class="image-upload-controls">
                            <button type="button" class="btn btn-outline btn-small" onclick="document.getElementById('hero_background_image_input').click();">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload Image
                            </button>
                            <?php if (!empty($website['hero_background_image'])): ?>
                                <button type="button" class="btn btn-outline btn-small" onclick="window.open('<?= htmlspecialchars($website['hero_background_image']); ?>', '_blank');">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                    View Image
                                </button>
                                <button type="button" class="btn btn-outline btn-small btn-danger" onclick="removeImage('hero_background_image');">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Remove
                                </button>
                            <?php endif; ?>
                        </div>
                        <small id="hero_background_image_status"></small>
                    </div>
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
            <h4>Intro Section</h4>
            <div class="form-group">
                <label>
                    <span>Intro Tagline</span>
                    <input type="text" name="intro_tagline" value="<?= htmlspecialchars($website['intro_tagline'] ?? 'Welcome'); ?>" placeholder="Welcome">
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Intro Heading</span>
                    <input type="text" name="intro_heading" value="<?= htmlspecialchars($website['intro_heading'] ?? ''); ?>" placeholder="A modern, warm, Kenyan hotel experience.">
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Intro Copy</span>
                    <textarea name="intro_copy" rows="4" placeholder="Every suite, plate, and playlist is curated from the Hotela command center, so your stay feels effortless."><?= htmlspecialchars($website['intro_copy'] ?? ''); ?></textarea>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h4>Homepage Banner</h4>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="banner_enabled" value="0">
                    <input type="checkbox" name="banner_enabled" value="1" <?= checked($website['banner_enabled'] ?? false); ?>>
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

        <div class="form-section">
            <h4>Restaurant & Bar</h4>
            <div class="form-group">
                <label>
                    <span>Restaurant Tagline</span>
                    <input type="text" name="restaurant_tagline" value="<?= htmlspecialchars($website['restaurant_tagline'] ?? 'Restaurant & Bar'); ?>" placeholder="Restaurant & Bar">
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Restaurant Title</span>
                    <input type="text" name="restaurant_title" value="<?= htmlspecialchars($website['restaurant_title'] ?? ''); ?>" placeholder="Sunrise breakfast to cocktail hour.">
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Restaurant Image</span>
                    <div class="image-upload-field">
                        <input type="file" id="restaurant_image_input" accept="image/*" style="display: none;">
                        <input type="hidden" name="restaurant_image" id="restaurant_image_url" value="<?= htmlspecialchars($website['restaurant_image'] ?? ''); ?>">
                        <div class="image-upload-controls">
                            <button type="button" class="btn btn-outline btn-small" onclick="document.getElementById('restaurant_image_input').click();">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload Image
                            </button>
                            <?php if (!empty($website['restaurant_image'])): ?>
                                <button type="button" class="btn btn-outline btn-small" onclick="window.open('<?= htmlspecialchars($website['restaurant_image']); ?>', '_blank');">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                    View Image
                                </button>
                                <button type="button" class="btn btn-outline btn-small btn-danger" onclick="removeImage('restaurant_image');">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Remove
                                </button>
                            <?php endif; ?>
                        </div>
                        <small id="restaurant_image_status"></small>
                    </div>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Restaurant CTA Text</span>
                    <input type="text" name="restaurant_cta_text" value="<?= htmlspecialchars($website['restaurant_cta_text'] ?? 'View Menu'); ?>" placeholder="View Menu">
                </label>
            </div>
        </div>
    </div>

    <!-- Pages Tab -->
    <div class="tab-content" data-content="pages">
        <h3>Page Visibility & Features</h3>
        
        <div class="form-section">
            <h4>Page Visibility</h4>
            <?php
            $pages = $website['pages'] ?? [];
            ?>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[rooms]" value="0">
                    <input type="checkbox" name="pages[rooms]" value="1" <?= checked($pages['rooms'] ?? true); ?>>
                    <span>Show Rooms page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[food]" value="0">
                    <input type="checkbox" name="pages[food]" value="1" <?= checked($pages['food'] ?? true); ?>>
                    <span>Show Drinks & Food page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[about]" value="0">
                    <input type="checkbox" name="pages[about]" value="1" <?= checked($pages['about'] ?? true); ?>>
                    <span>Show About page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[contact]" value="0">
                    <input type="checkbox" name="pages[contact]" value="1" <?= checked($pages['contact'] ?? true); ?>>
                    <span>Show Contact page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[conferencing]" value="0">
                    <input type="checkbox" name="pages[conferencing]" value="1" <?= checked($pages['conferencing'] ?? false); ?>>
                    <span>Show Conferencing page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[events]" value="0">
                    <input type="checkbox" name="pages[events]" value="1" <?= checked($pages['events'] ?? false); ?>>
                    <span>Show Events page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[gallery]" value="0">
                    <input type="checkbox" name="pages[gallery]" value="1" <?= checked($pages['gallery'] ?? false); ?>>
                    <span>Show Gallery page</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="pages[order]" value="0">
                    <input type="checkbox" name="pages[order]" value="1" <?= checked($pages['order'] ?? true); ?>>
                    <span>Show Order | Booking page</span>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h4>Features</h4>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="booking_enabled" value="0">
                    <input type="checkbox" name="booking_enabled" value="1" <?= checked($website['booking_enabled'] ?? true); ?>>
                    <span>Enable booking engine</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="order_enabled" value="0">
                    <input type="checkbox" name="order_enabled" value="1" <?= checked($website['order_enabled'] ?? true); ?>>
                    <span>Enable food ordering</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="online_payment_enabled" value="0">
                    <input type="checkbox" name="online_payment_enabled" value="1" <?= checked($website['online_payment_enabled'] ?? false); ?>>
                    <span>Enable online payment (M-Pesa) for Bookings</span>
                    <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">When enabled, guests can pay for bookings using M-Pesa. Make sure M-Pesa is configured in Payment Gateways settings.</small>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h4>Website Order Payment Methods</h4>
            <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">Select which payment methods customers can use when placing orders on your website. Only payment gateways that are configured and enabled in <strong>Payment Gateways</strong> settings will appear here. At least one payment method must be enabled.</p>
            <?php
            // Get enabled payment methods from website settings
            $enabledPaymentMethods = $website['enabled_payment_methods'] ?? ['cash']; // Default to cash only
            if (!is_array($enabledPaymentMethods)) {
                // Backward compatibility: if enable_mpesa_orders exists, convert it
                if (!empty($website['enable_mpesa_orders'])) {
                    $enabledPaymentMethods = ['cash', 'mpesa'];
                } else {
                    $enabledPaymentMethods = ['cash'];
                }
            }
            
            // Get configured payment gateways from settings
            $paymentGateways = $settings['payment_gateways'] ?? [];
            
            // Define available payment methods with their metadata
            $availablePaymentMethods = [
                'cash' => [
                    'label' => 'Cash on Delivery/Pickup',
                    'description' => 'Customers pay with cash when receiving their order',
                    'icon' => '💵',
                    'always_available' => true, // Cash is always available
                ],
                'mpesa' => [
                    'label' => 'M-Pesa',
                    'description' => 'Customers pay using M-Pesa mobile money',
                    'icon' => '💰',
                    'always_available' => false,
                ],
                'card' => [
                    'label' => 'Card Payments',
                    'description' => 'Credit and debit card processing',
                    'icon' => '💳',
                    'always_available' => false,
                ],
                'stripe' => [
                    'label' => 'Stripe',
                    'description' => 'Online payment processing via Stripe',
                    'icon' => '💳',
                    'always_available' => false,
                ],
                'paypal' => [
                    'label' => 'PayPal',
                    'description' => 'PayPal online payment system',
                    'icon' => '💳',
                    'always_available' => false,
                ],
                'bank' => [
                    'label' => 'Bank Transfer',
                    'description' => 'Direct bank transfer payments',
                    'icon' => '🏦',
                    'always_available' => false,
                ],
            ];
            
            // Filter payment methods: only show those that are configured and enabled in Payment Gateways
            // OR always show cash (it's always available)
            $paymentMethods = [];
            foreach ($availablePaymentMethods as $methodKey => $methodInfo) {
                if ($methodInfo['always_available']) {
                    // Cash is always available
                    $paymentMethods[$methodKey] = $methodInfo;
                } elseif (isset($paymentGateways[$methodKey]) && !empty($paymentGateways[$methodKey]['enabled'])) {
                    // Only show if gateway is configured and enabled
                    $paymentMethods[$methodKey] = $methodInfo;
                }
            }
            ?>
            <?php if (empty($paymentMethods)): ?>
                <div style="padding: 2rem; text-align: center; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 0.75rem; color: #92400e;">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 600;">No Payment Gateways Configured</p>
                    <p style="margin: 0; font-size: 0.9rem;">Please configure and enable at least one payment gateway in <a href="<?= base_url('staff/admin/settings?tab=payment-gateway'); ?>" style="color: #92400e; text-decoration: underline;">Payment Gateways</a> settings. Cash payment is always available.</p>
                </div>
            <?php else: ?>
                <div class="payment-methods-grid" style="display: grid; gap: 1rem;">
                    <?php foreach ($paymentMethods as $methodKey => $methodInfo): ?>
                        <div class="payment-method-card" style="padding: 1.25rem; background: #fff; border: 2px solid <?= in_array($methodKey, $enabledPaymentMethods) ? '#8b5cf6' : '#e2e8f0'; ?>; border-radius: 0.75rem; transition: all 0.2s ease;">
                            <label class="checkbox" style="margin: 0; cursor: pointer;">
                                <input type="hidden" name="enabled_payment_methods[<?= $methodKey; ?>]" value="0">
                                <input type="checkbox" 
                                       name="enabled_payment_methods[<?= $methodKey; ?>]" 
                                       value="1" 
                                       <?= in_array($methodKey, $enabledPaymentMethods) ? 'checked' : ''; ?>
                                       onchange="updatePaymentMethodCard(this, '<?= $methodKey; ?>')"
                                       <?= $methodKey === 'cash' ? 'disabled' : ''; ?>
                                       title="<?= $methodKey === 'cash' ? 'Cash is always enabled' : ''; ?>">
                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <span style="font-size: 1.25rem;"><?= htmlspecialchars($methodInfo['icon'] ?? ''); ?></span>
                                            <span style="font-weight: 600; font-size: 1rem; color: #1e293b;">
                                                <?= htmlspecialchars($methodInfo['label']); ?>
                                            </span>
                                            <?php if ($methodKey === 'cash'): ?>
                                                <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #dbeafe; color: #1e40af; border-radius: 0.25rem; font-weight: 500;">Always Available</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="display: block; color: #64748b; font-size: 0.875rem; line-height: 1.5;">
                                            <?= htmlspecialchars($methodInfo['description']); ?>
                                        </small>
                                    </div>
                                    <div style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid <?= in_array($methodKey, $enabledPaymentMethods) ? '#8b5cf6' : '#cbd5e1'; ?>; background: <?= in_array($methodKey, $enabledPaymentMethods) ? '#8b5cf6' : '#fff'; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s ease;">
                                        <?php if (in_array($methodKey, $enabledPaymentMethods)): ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <small style="display: block; margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                <strong>Note:</strong> Cash payment is always enabled. Other payment methods will only appear here after they are configured and enabled in <a href="<?= base_url('staff/admin/settings?tab=payment-gateway'); ?>" style="color: #8b5cf6; text-decoration: underline;">Payment Gateways</a> settings.
            </small>
        </div>

        <div class="form-section">
            <h4>Display Options</h4>
            <div class="form-group">
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
            </div>
            <div class="form-group">
                <label class="checkbox">
                    <input type="hidden" name="powered_by_hotela" value="0">
                    <input type="checkbox" name="powered_by_hotela" value="1" <?= checked($website['powered_by_hotela'] ?? true); ?>>
                    <span>Show "Powered by Hotela" in footer</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Content Tab -->
    <div class="tab-content" data-content="content">
        <h3>Page Content</h3>
        
        <div class="form-section">
            <h4>Page Introductions</h4>
            <div class="form-group">
                <label>
                    <span>Rooms Page Introduction</span>
                    <textarea name="rooms_intro" rows="4" placeholder="Comfort-forward suites fitted for rest, work, and play."><?= htmlspecialchars($website['rooms_intro'] ?? ''); ?></textarea>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Food & Drinks Page Introduction</span>
                    <textarea name="food_intro" rows="4" placeholder="From sunrise breakfasts to late-night cocktails."><?= htmlspecialchars($website['food_intro'] ?? ''); ?></textarea>
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
            <h4>Contact Details</h4>
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
                    <textarea name="contact_message" rows="4" placeholder="Call or message our team for bespoke itineraries and offers."><?= htmlspecialchars($website['contact_message'] ?? ''); ?></textarea>
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
            <h4>Meta Tags</h4>
            <div class="form-group">
                <label>
                    <span>Meta Title</span>
                    <input type="text" name="meta_title" value="<?= htmlspecialchars($website['meta_title'] ?? ''); ?>" placeholder="Hotela - Your Perfect Stay">
                    <small>Appears in browser tabs and search results</small>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Meta Description</span>
                    <textarea name="meta_description" rows="3" placeholder="Experience luxury and comfort..."><?= htmlspecialchars($website['meta_description'] ?? ''); ?></textarea>
                    <small>Brief description for search engines (150-160 characters recommended)</small>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <span>Meta Keywords</span>
                    <input type="text" name="meta_keywords" value="<?= htmlspecialchars($website['meta_keywords'] ?? ''); ?>" placeholder="hotel, accommodation, booking">
                    <small>Comma-separated keywords for SEO</small>
                </label>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.website-tabs .tab');
    const contents = document.querySelectorAll('.tab-content');
    
    if (tabs.length === 0 || contents.length === 0) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const targetContent = document.querySelector(`[data-content="${targetTab}"]`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});
</script>

<style>
.website-settings-wrapper {
    width: 100%;
}

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

.website-header h4 {
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

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.website-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    flex-wrap: wrap;
    overflow-x: auto;
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
    white-space: nowrap;
}

.website-tabs .tab:hover {
    color: #475569;
}

.website-tabs .tab.active {
    color: #8b5cf6;
    border-bottom-color: #8b5cf6;
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
.form-group input[type="color"],
.form-group textarea,
.form-group select {
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

.form-group input[type="color"] {
    height: 48px;
    cursor: pointer;
    padding: 0.25rem;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.form-group small {
    color: #64748b;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    font-weight: 400;
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
    flex-shrink: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.image-upload-field {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.image-upload-controls {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.image-upload-controls .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.image-upload-controls .btn-danger {
    color: #dc2626;
    border-color: #dc2626;
}

.image-upload-controls .btn-danger:hover {
    background: #dc2626;
    color: #fff;
}

#hero_background_image_status,
#restaurant_image_status {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #64748b;
}

#hero_background_image_status.success,
#restaurant_image_status.success {
    color: #16a34a;
}

#hero_background_image_status.error,
#restaurant_image_status.error {
    color: #dc2626;
}

@media (max-width: 768px) {
    .website-header {
        flex-direction: column;
    }

    .website-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .image-upload-controls {
        flex-direction: column;
    }

    .image-upload-controls .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Image upload handlers
document.addEventListener('DOMContentLoaded', function() {
    // Hero background image upload
    const heroInput = document.getElementById('hero_background_image_input');
    if (heroInput) {
        heroInput.addEventListener('change', function(e) {
            uploadImage(e.target.files[0], 'hero_background_image');
        });
    }

    // Restaurant image upload
    const restaurantInput = document.getElementById('restaurant_image_input');
    if (restaurantInput) {
        restaurantInput.addEventListener('change', function(e) {
            uploadImage(e.target.files[0], 'restaurant_image');
        });
    }
});

function uploadImage(file, fieldName) {
    if (!file) return;

    // Validate file type
    if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/)) {
        showStatus(fieldName, 'Please select a valid image file (JPEG, PNG, GIF, or WebP)', 'error');
        return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showStatus(fieldName, 'File size must be less than 5MB', 'error');
        return;
    }

    showStatus(fieldName, 'Uploading...', '');

    const formData = new FormData();
    formData.append('image', file);

    fetch('<?= base_url('staff/admin/settings/upload-image'); ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.url) {
            // Update hidden input with the URL
            document.getElementById(fieldName + '_url').value = data.url;
            
            // Update view button
            updateViewButton(fieldName, data.url);
            
            showStatus(fieldName, 'Image uploaded successfully!', 'success');
            
            // Clear the file input
            document.getElementById(fieldName + '_input').value = '';
        } else {
            showStatus(fieldName, data.error || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showStatus(fieldName, 'Upload failed. Please try again.', 'error');
    });
}

function updateViewButton(fieldName, url) {
    const controls = document.querySelector(`#${fieldName}_url`).parentElement.querySelector('.image-upload-controls');
    if (!controls) return;

    // Check if view button already exists
    let viewBtn = controls.querySelector('.btn-view-image');
    if (!viewBtn) {
        // Create view button
        viewBtn = document.createElement('button');
        viewBtn.type = 'button';
        viewBtn.className = 'btn btn-outline btn-small btn-view-image';
        viewBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            View Image
        `;
        viewBtn.onclick = () => window.open(url, '_blank');
        
        // Insert after upload button
        const uploadBtn = controls.querySelector('button[onclick*="click()"]');
        if (uploadBtn) {
            uploadBtn.parentNode.insertBefore(viewBtn, uploadBtn.nextSibling);
        }
    } else {
        viewBtn.onclick = () => window.open(url, '_blank');
    }

    // Check if remove button exists
    let removeBtn = controls.querySelector('.btn-remove-image');
    if (!removeBtn) {
        removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-outline btn-small btn-danger btn-remove-image';
        removeBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Remove
        `;
        removeBtn.onclick = () => removeImage(fieldName);
        controls.appendChild(removeBtn);
    }
}

function removeImage(fieldName) {
    if (confirm('Are you sure you want to remove this image?')) {
        document.getElementById(fieldName + '_url').value = '';
        
        // Remove view and remove buttons
        const controls = document.querySelector(`#${fieldName}_url`).parentElement.querySelector('.image-upload-controls');
        if (controls) {
            const viewBtn = controls.querySelector('.btn-view-image');
            const removeBtn = controls.querySelector('.btn-remove-image');
            if (viewBtn) viewBtn.remove();
            if (removeBtn) removeBtn.remove();
        }
        
        showStatus(fieldName, 'Image removed', 'success');
        setTimeout(() => {
            showStatus(fieldName, '', '');
        }, 2000);
    }
}

function showStatus(fieldName, message, type) {
    const statusEl = document.getElementById(fieldName + '_status');
    if (statusEl) {
        statusEl.textContent = message;
        statusEl.className = type ? type : '';
    }
}

function updatePaymentMethodCard(checkbox, methodKey) {
    // Cash is always enabled, prevent unchecking
    if (methodKey === 'cash' && !checkbox.checked) {
        checkbox.checked = true;
        return;
    }
    
    const card = checkbox.closest('.payment-method-card');
    if (!card) return;
    
    if (checkbox.checked) {
        card.style.borderColor = '#8b5cf6';
        const indicator = card.querySelector('div[style*="border-radius: 50%"]');
        if (indicator) {
            indicator.style.borderColor = '#8b5cf6';
            indicator.style.background = '#8b5cf6';
            indicator.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `;
        }
    } else {
        card.style.borderColor = '#e2e8f0';
        const indicator = card.querySelector('div[style*="border-radius: 50%"]');
        if (indicator) {
            indicator.style.borderColor = '#cbd5e1';
            indicator.style.background = '#fff';
            indicator.innerHTML = '';
        }
    }
    
    // Validate at least one payment method is selected (cash is always selected)
    const allCheckboxes = document.querySelectorAll('input[name^="enabled_payment_methods"]:not([disabled])');
    const checkedCount = Array.from(allCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        // Re-enable cash by default (shouldn't happen, but safety check)
        setTimeout(() => {
            const cashCheckbox = document.querySelector('input[name="enabled_payment_methods[cash]"]');
            if (cashCheckbox && !cashCheckbox.checked) {
                cashCheckbox.checked = true;
                updatePaymentMethodCard(cashCheckbox, 'cash');
            }
        }, 100);
    }
}
</script>
