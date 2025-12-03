<?php
$website = $website ?? settings('website', []);
$guestUser = \App\Support\GuestPortal::user();
$mpesaEnabled = !empty($website['enable_mpesa_orders']);
$reorderSuccess = $reorderSuccess ?? null;
$reorderRef = $reorderRef ?? null;
$slot = function () use ($categories, $website, $guestUser, $mpesaEnabled, $reorderSuccess, $reorderRef) {
    ob_start(); 
    $restaurantImage = $website['restaurant_image'] ?? null;
    $foodIntro = $website['food_intro'] ?? 'Experience culinary excellence with our carefully curated menu featuring the finest ingredients and flavors.';
    ?>
    
    <?php if ($reorderSuccess === 'success' && $reorderRef): ?>
        <div style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 1rem 1.5rem; background: #d1fae5; color: #065f46; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 4px solid #16a34a; max-width: 400px;">
            <strong>✓ Items Added to Cart!</strong>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                Items from order <strong><?= htmlspecialchars($reorderRef); ?></strong> have been added to your cart. Review and checkout when ready.
            </p>
        </div>
        <script>
            setTimeout(function() {
                const msg = document.querySelector('div[style*="d1fae5"]');
                if (msg) msg.style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>
    
    <!-- Hero Section -->
    <section class="dining-hero">
        <?php if ($restaurantImage): ?>
            <div class="dining-hero__image" style="background-image: url('<?= asset($restaurantImage); ?>');"></div>
        <?php else: ?>
            <div class="dining-hero__image dining-hero__image--default"></div>
        <?php endif; ?>
        <div class="dining-hero__overlay"></div>
        <div class="dining-hero__content">
            <div class="container">
                <h1 class="dining-hero__title">Dine & Drink</h1>
                <p class="dining-hero__subtitle"><?= htmlspecialchars($foodIntro); ?></p>
            </div>
        </div>
    </section>

    <!-- Category Navigation -->
    <section class="dining-categories">
        <div class="container">
            <div class="category-tabs" id="categoryTabs">
                <button class="category-tab active" data-category="all">All Menu</button>
                <?php foreach ($categories as $category): ?>
                    <button class="category-tab" data-category="<?= htmlspecialchars(strtolower($category['name'])); ?>">
                        <?= htmlspecialchars($category['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Search & Filters -->
    <section class="dining-filters">
        <div class="container">
            <form id="menuFilters" class="dining-filters__form">
                <div class="dining-filters__grid">
                    <label class="dining-filters__field">
                        <span>Search</span>
                        <input type="text" name="query" placeholder="Search menu items...">
                    </label>
                    <label class="dining-filters__field">
                        <span>Category</span>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars(strtolower($category['name'])); ?>">
                                    <?= htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="dining-filters__field">
                        <span>Max Price</span>
                        <input type="number" name="max_price" min="0" placeholder="KES">
                    </label>
                </div>
            </form>
        </div>
    </section>

    <!-- Menu Items -->
    <section class="dining-menu" id="menu">
        <div class="container">
            <div class="product-grid" id="productGrid">
                <?php 
                $categoryIcons = settings('website.category_icons', []);
                $categoryIconClasses = settings('website.category_icon_classes', []);
                $categoryEmojis = [
                    'breakfast' => '🥐',
                    'lunch' => '🍔',
                    'dinner' => '🍽️',
                    'snacks' => '🍪',
                    'soft drinks' => '🥤',
                    'alcohol' => '🍷',
                    'specials' => '⭐',
                ];
                
                foreach ($categories as $category): 
                    foreach ($category['items'] as $item): 
                        $itemId = (int)($item['id'] ?? 0);
                        $title = $item['name'] ?? 'Menu Item';
                        $price = (float)($item['price'] ?? 0);
                        $desc = !empty($item['description']) ? $item['description'] : '';
                        $photo = $item['photo_url'] ?? null;
                        $isAvailable = !isset($item['available']) || $item['available'] === true || $item['available'] === 1;
                        $key = strtolower($category['name']);
                        $fa = $categoryIconClasses[$key] ?? $categoryIconClasses[strtolower(trim($key))] ?? $categoryIconClasses[str_replace('-', ' ', $key)] ?? null;
                        if (!$photo) {
                            $photo = $categoryIcons[$key] ?? null;
                        }
                        $search = strtolower($title . ' ' . $desc . ' ' . ($category['name'] ?? ''));
                        ?>
                        <article class="product-card<?= !$isAvailable ? ' product-card--unavailable' : ''; ?>" 
                                 data-id="<?= $itemId; ?>" 
                                 data-name="<?= htmlspecialchars($search); ?>" 
                                 data-price="<?= $price; ?>" 
                                 data-category="<?= htmlspecialchars(strtolower($category['name'])); ?>"
                                 data-available="<?= $isAvailable ? '1' : '0'; ?>"
                                 <?= !$isAvailable ? 'aria-disabled="true"' : ''; ?>>
                            <?php if (!$isAvailable): ?>
                                <div class="product-card__badge product-card__badge--unavailable">Unavailable</div>
                            <?php endif; ?>
                            <div class="product-card__media">
                                <?php if ($photo): ?>
                                    <img src="<?= asset($photo); ?>" alt="<?= htmlspecialchars($title); ?>" loading="lazy">
                                <?php elseif ($fa): ?>
                                    <i class="<?= htmlspecialchars($fa); ?> product-card__icon" aria-hidden="true"></i>
                                    <span class="product-card__emoji" aria-hidden="true"><?= htmlspecialchars($categoryEmojis[$key] ?? '🍽️'); ?></span>
                                <?php else: ?>
                                    <i class="fa-solid fa-utensils product-card__icon" aria-hidden="true"></i>
                                    <span class="product-card__emoji" aria-hidden="true"><?= htmlspecialchars($categoryEmojis[$key] ?? '🍽️'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-card__body">
                                <div class="product-card__header">
                                    <h3 class="product-card__title"><?= htmlspecialchars($title); ?></h3>
                                    <span class="product-card__price">KES <?= number_format($price, 0); ?></span>
                                </div>
                                <?php if ($desc): ?>
                                    <p class="product-card__desc"><?= htmlspecialchars($desc); ?></p>
                                <?php endif; ?>
                                <div class="product-card__actions">
                                    <div class="product-card__qty" data-qty>
                                        <button type="button" class="qty-btn" aria-label="Decrease" data-dec <?= !$isAvailable ? 'disabled' : ''; ?>>-</button>
                                        <span class="qty-value">1</span>
                                        <button type="button" class="qty-btn" aria-label="Increase" data-inc <?= !$isAvailable ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                    <button class="product-card__add-btn" data-add <?= !$isAvailable ? 'disabled' : ''; ?>>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12h14"></path>
                                        </svg>
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Floating Cart -->
    <div class="cart-fab" id="cartFab">
        <button type="button" id="cartFabBtn" class="cart-fab__btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span>Cart</span>
            <span class="cart-fab__count" id="cartFabCount">0</span>
        </button>
    </div>

    <!-- Mini Cart -->
    <div class="mini-cart" id="miniCart">
        <header class="mini-cart__header">
            <h3>My Order</h3>
            <button class="mini-cart__close" id="miniCartClose" aria-label="Close cart">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </header>
        <div class="mini-cart__body">
            <ul id="miniCartLines" class="mini-cart__lines"></ul>
        </div>
        <footer class="mini-cart__footer">
            <form id="checkoutForm" method="post" action="<?= base_url('order/checkout'); ?>">
                <div class="mini-cart__checkout">
                    <div id="checkoutFields" class="checkout-fields" style="display: none;">
                        <div class="checkout-field">
                            <label>Name *</label>
                            <input type="text" name="guest_name" required placeholder="Your name">
                        </div>
                        <div class="checkout-field">
                            <label>Phone *</label>
                            <input type="tel" name="guest_phone" required placeholder="254712345678">
                        </div>
                        <div class="checkout-field">
                            <label>Email</label>
                            <input type="email" name="guest_email" placeholder="your@email.com">
                        </div>
                        <div class="checkout-field">
                            <label>Service Type *</label>
                            <select name="service_type" required>
                                <option value="eat_in">Eat In</option>
                                <option value="pickup">Pickup</option>
                                <option value="delivery">Delivery</option>
                                <option value="room_service">Room Service</option>
                            </select>
                        </div>
                        <div id="roomNumberField" class="checkout-field" style="display: none;">
                            <label>Room Number</label>
                            <input type="text" name="room_number" placeholder="Room number">
                        </div>
                        <div class="checkout-field">
                            <label>Payment Method *</label>
                            <select name="payment_method" required id="payment-method-select">
                                <?php
                                // Get enabled payment methods from website settings
                                $websiteSettings = settings('website', []);
                                $enabledPaymentMethods = $websiteSettings['enabled_payment_methods'] ?? ['cash'];
                                
                                // Backward compatibility
                                if (!is_array($enabledPaymentMethods)) {
                                    if (!empty($websiteSettings['enable_mpesa_orders'])) {
                                        $enabledPaymentMethods = ['cash', 'mpesa'];
                                    } else {
                                        $enabledPaymentMethods = ['cash'];
                                    }
                                }
                                
                                // Ensure at least cash is enabled
                                if (empty($enabledPaymentMethods)) {
                                    $enabledPaymentMethods = ['cash'];
                                }
                                
                                // Get configured payment gateways
                                $paymentGateways = settings('payment_gateways', []);
                                
                                // Define payment method labels and icons
                                $paymentMethodInfo = [
                                    'cash' => ['label' => 'Cash on Delivery/Pickup', 'icon' => '💵'],
                                    'mpesa' => ['label' => 'M-Pesa', 'icon' => '💰'],
                                    'card' => ['label' => 'Card Payment', 'icon' => '💳'],
                                    'stripe' => ['label' => 'Stripe', 'icon' => '💳'],
                                    'paypal' => ['label' => 'PayPal', 'icon' => '💳'],
                                    'bank' => ['label' => 'Bank Transfer', 'icon' => '🏦'],
                                ];
                                
                                // Filter: only show methods that are:
                                // 1. In enabledPaymentMethods array, AND
                                // 2. Either 'cash' (always available) OR configured and enabled in payment_gateways
                                $availableMethods = [];
                                foreach ($enabledPaymentMethods as $method) {
                                    if ($method === 'cash') {
                                        // Cash is always available
                                        $availableMethods[] = $method;
                                    } elseif (isset($paymentGateways[$method]) && !empty($paymentGateways[$method]['enabled'])) {
                                        // Only show if gateway is configured and enabled
                                        $availableMethods[] = $method;
                                    }
                                }
                                
                                // Ensure at least cash is available
                                if (empty($availableMethods)) {
                                    $availableMethods = ['cash'];
                                }
                                
                                foreach ($availableMethods as $method): 
                                    if (isset($paymentMethodInfo[$method])):
                                ?>
                                    <option value="<?= htmlspecialchars($method); ?>">
                                        <?= htmlspecialchars($paymentMethodInfo[$method]['icon'] . ' ' . $paymentMethodInfo[$method]['label']); ?>
                                    </option>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </select>
                        </div>
                        <div id="mpesaPhoneField" class="checkout-field" style="display: none;">
                            <label>M-Pesa Phone *</label>
                            <input type="tel" name="mpesa_phone" placeholder="254712345678">
                        </div>
                        <div class="checkout-field">
                            <label>Special Instructions</label>
                            <textarea name="instructions" rows="2" placeholder="Any special requests..."></textarea>
                        </div>
                    </div>
                    <div class="mini-cart__total">
                        <span class="total-label">Total:</span>
                        <strong id="miniCartTotal" class="total-amount">KES 0</strong>
                    </div>
                    <button class="checkout-btn" type="button" id="checkoutBtn">Checkout</button>
                </div>
            </form>
        </footer>
    </div>

    <style>
        /* Hero Section */
        .dining-hero {
            position: relative;
            height: 60vh;
            min-height: 400px;
            max-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 3rem;
        }
        .dining-hero__image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .dining-hero__image--default {
            background: linear-gradient(135deg, var(--primary) 0%, #a67c52 100%);
        }
        .dining-hero__overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%);
        }
        .dining-hero__content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #fff;
            width: 100%;
        }
        .dining-hero__title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin: 0 0 1rem 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            letter-spacing: -0.02em;
        }
        .dining-hero__subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
            text-shadow: 0 1px 5px rgba(0,0,0,0.3);
        }

        /* Category Tabs */
        .dining-categories {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .category-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 0.5rem 0;
            scrollbar-width: thin;
        }
        .category-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border-radius: 999px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .category-tab:hover {
            background: #f8fafc;
            color: var(--primary);
        }
        .category-tab.active {
            background: var(--primary);
            color: #fff;
        }

        /* Filters */
        .dining-filters {
            margin-bottom: 2rem;
        }
        .dining-filters__form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 1rem;
        }
        .dining-filters__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .dining-filters__field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .dining-filters__field label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
        }
        .dining-filters__field:first-child {
            position: relative;
        }
        .dining-filters__field:first-child span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dining-filters__field:first-child span::before {
            content: '';
            width: 18px;
            height: 18px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cpath d='m21 21-4.35-4.35'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            display: inline-block;
            flex-shrink: 0;
        }
        .dining-filters__field:first-child input {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .dining-filters__field input,
        .dining-filters__field select {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            background: #fff;
            transition: all 0.2s ease;
            width: 100%;
            box-sizing: border-box;
        }
        .dining-filters__field input:focus,
        .dining-filters__field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .product-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        .product-card[aria-disabled="true"] {
            opacity: 0.5;
            pointer-events: none;
            position: relative;
        }
        .product-card[aria-disabled="true"]::after {
            content: 'Unavailable';
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            background: #fee2e2;
            color: #991b1b;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            z-index: 1;
        }
        .product-card__media {
            position: relative;
            width: 100%;
            height: 200px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-card__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-card__icon {
            font-size: 64px;
            color: rgba(15,23,42,0.2);
        }
        .product-card__emoji {
            font-size: 56px;
            line-height: 1;
        }
        .product-card__body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
        }
        .product-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .product-card__title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            flex: 1;
        }
        .product-card__price {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
        }
        .product-card__desc {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0;
            flex: 1;
        }
        .product-card__actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-top: auto;
        }
        .product-card__qty {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 0.25rem;
            background: #f8fafc;
        }
        .qty-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            color: var(--primary);
            transition: all 0.2s ease;
        }
        .qty-btn:hover {
            background: var(--primary);
            color: #fff;
        }
        .qty-value {
            min-width: 28px;
            text-align: center;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .product-card__add-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .product-card__add-btn:hover {
            background: #a67c52;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(138, 106, 63, 0.3);
        }

        /* Floating Cart */
        .cart-fab {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            z-index: 1000;
            display: none;
        }
        .cart-fab.visible {
            display: block;
        }
        .cart-fab__btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        .cart-fab__btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.3);
        }
        .cart-fab__count {
            background: #fff;
            color: var(--primary);
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            font-weight: 700;
            font-size: 0.875rem;
        }

        /* Mini Cart */
        .mini-cart {
            position: fixed;
            right: 1.5rem;
            bottom: 5.5rem;
            width: min(92vw, 400px);
            max-height: 85vh;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            z-index: 999;
            overflow: hidden;
        }
        .mini-cart.has-checkout-fields {
            max-height: 90vh;
        }
        .mini-cart.open {
            display: flex;
        }
        .mini-cart__header {
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        .mini-cart__header h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }
        .mini-cart__close {
            background: transparent;
            border: none;
            cursor: pointer;
            color: #64748b;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        .mini-cart__close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .mini-cart__body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            min-height: 0;
            max-height: 100%;
        }
        .mini-cart__lines {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .mini-cart__lines li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        .mini-cart__lines li > div:first-child {
            flex: 1;
        }
        .mini-cart__lines li strong {
            display: block;
            font-size: 0.95rem;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }
        .mini-cart__lines li small {
            font-size: 0.85rem;
            color: #64748b;
        }
        .mini-cart__lines .row-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .mini-cart__lines [data-dec],
        .mini-cart__lines [data-inc] {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: var(--primary);
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .mini-cart__lines [data-dec]:hover,
        .mini-cart__lines [data-inc]:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .mini-cart__lines [data-remove] {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .mini-cart__lines [data-remove]:hover {
            background: #f87171;
            color: #fff;
            border-color: #f87171;
        }
        .mini-cart__footer {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            flex-shrink: 0;
            overflow-y: auto;
            max-height: 50vh;
        }
        .mini-cart__checkout {
            padding: 1.25rem;
        }
        .checkout-fields {
            display: grid;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 0.5rem;
            max-height: 40vh;
            overflow-y: auto;
        }
        .checkout-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .checkout-field label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
        }
        .checkout-field input,
        .checkout-field select,
        .checkout-field textarea {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.95rem;
        }
        .mini-cart__total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 0.5rem;
        }
        .total-label {
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
        }
        .total-amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .checkout-btn:hover {
            background: #a67c52;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(138, 106, 63, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            .dining-filters__grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dining-hero {
                height: 50vh;
                min-height: 300px;
                margin-bottom: 2rem;
            }
            .dining-hero__title {
                font-size: 2rem;
            }
            .dining-hero__subtitle {
                font-size: 0.95rem;
                padding: 0 1rem;
            }
            
            .dining-categories {
                padding: 1rem 0;
                margin-bottom: 1.5rem;
            }
            .category-tabs {
                padding: 0.5rem 1rem;
                gap: 0.5rem;
            }
            .category-tab {
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }
            
            .dining-filters {
                margin-bottom: 1.5rem;
            }
            .dining-filters__form {
                padding: 1rem;
            }
            .dining-filters__grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .dining-filters__field input,
            .dining-filters__field select {
                padding: 0.875rem 1rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            .dining-filters__field:first-child input {
                padding-left: 1rem;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
                margin-bottom: 3rem;
            }
            .product-card__media {
                height: 180px;
            }
            .product-card__body {
                padding: 1rem;
            }
            .product-card__title {
                font-size: 1rem;
            }
            .product-card__price {
                font-size: 1rem;
            }
            .product-card__actions {
                flex-wrap: wrap;
            }
            .product-card__add-btn {
                width: 100%;
                margin-top: 0.5rem;
            }
            
            .mini-cart {
                right: 0.5rem;
                left: 0.5rem;
                width: auto;
                bottom: 4.5rem;
                max-height: 75vh;
            }
            .cart-fab {
                right: 1rem;
                bottom: 1rem;
            }
            .cart-fab__btn {
                padding: 0.875rem 1.25rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .dining-hero {
                height: 40vh;
                min-height: 250px;
            }
            .dining-hero__title {
                font-size: 1.75rem;
            }
            .dining-hero__subtitle {
                font-size: 0.875rem;
            }
            
            .category-tab {
                padding: 0.5rem 1rem;
                font-size: 0.8125rem;
            }
            
            .dining-filters__form {
                padding: 0.875rem;
            }
            .dining-filters__field input,
            .dining-filters__field select {
                padding: 0.75rem 0.875rem;
            }
            .dining-filters__field:first-child input {
                padding-left: 0.875rem;
            }
            .dining-filters__field:first-child span::before {
                width: 16px;
                height: 16px;
            }
            
            .product-card__media {
                height: 160px;
            }
            .product-card__body {
                padding: 0.875rem;
            }
            .product-card__header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .product-card__actions {
                width: 100%;
            }
            .product-card__qty {
                flex: 1;
            }
            
            .mini-cart {
                right: 0.25rem;
                left: 0.25rem;
                bottom: 4rem;
                max-height: 85vh;
            }
            .mini-cart.has-checkout-fields {
                max-height: 90vh;
            }
            .mini-cart__header,
            .mini-cart__checkout {
                padding: 1rem;
            }
            .mini-cart__footer {
                max-height: 45vh;
            }
            .checkout-fields {
                padding: 0.875rem;
                gap: 0.875rem;
                max-height: 35vh;
            }
            .checkout-field input,
            .checkout-field select,
            .checkout-field textarea {
                padding: 0.625rem 0.875rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .cart-fab {
                right: 0.75rem;
                bottom: 0.75rem;
            }
            .cart-fab__btn {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }

        @media (max-width: 360px) {
            .dining-hero__title {
                font-size: 1.5rem;
            }
            .category-tab {
                padding: 0.5rem 0.875rem;
                font-size: 0.75rem;
            }
            .product-card__media {
                height: 140px;
            }
        }
    </style>

    <script>
        (function() {
            const api = "<?= base_url('order/cart'); ?>";
            const fab = document.getElementById('cartFab');
            const fabBtn = document.getElementById('cartFabBtn');
            const fabCount = document.getElementById('cartFabCount');
            const mini = document.getElementById('miniCart');
            const miniLines = document.getElementById('miniCartLines');
            const miniTotal = document.getElementById('miniCartTotal');
            const checkoutFields = document.getElementById('checkoutFields');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const checkoutForm = document.getElementById('checkoutForm');
            const serviceTypeSelect = checkoutFields?.querySelector('[name="service_type"]');
            const paymentMethodSelect = checkoutFields?.querySelector('[name="payment_method"]');
            const roomNumberField = document.getElementById('roomNumberField');
            const mpesaPhoneField = document.getElementById('mpesaPhoneField');
            const categoryTabs = document.getElementById('categoryTabs');
            
            const userInfo = <?= json_encode($guestUser ?: []); ?>;

            function formatKES(v) {
                return 'KES ' + Number(v || 0).toLocaleString(undefined, {minimumFractionDigits: 0});
            }

            function updateCartUI(cart) {
                const lines = cart.lines || [];
                const total = cart.total || 0;
                const count = lines.reduce((sum, line) => sum + (line.qty || 0), 0);

                miniLines.innerHTML = lines.length ? lines.map(line => `
                    <li data-id="${line.id}">
                        <div>
                            <strong>${line.name || 'Item'}</strong>
                            <small>${formatKES(line.price || 0)} × ${line.qty || 0}</small>
                        </div>
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-small" data-dec type="button">-</button>
                            <span data-qty>${line.qty || 0}</span>
                            <button class="btn btn-ghost btn-small" data-inc type="button">+</button>
                            <button class="btn btn-outline btn-small" data-remove type="button">&times;</button>
                        </div>
                    </li>
                `).join('') : '<li style="text-align: center; padding: 1rem; color: #64748b;">Your cart is empty.</li>';

                miniTotal.textContent = formatKES(total);
                fabCount.textContent = count;
                
                if (count > 0) {
                    fab.classList.add('visible');
                } else {
                    fab.classList.remove('visible');
                    mini.classList.remove('open');
                }
            }

            function loadCart() {
                fetch(api)
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok && data.cart) {
                            updateCartUI(data.cart);
                        }
                    })
                    .catch(err => console.error('Cart load error:', err));
            }

            function addToCart(itemId, quantity) {
                // Check if item is available before adding
                const card = document.querySelector(`.product-card[data-id="${itemId}"]`);
                if (card) {
                    const isAvailable = card.dataset.available !== '0' && !card.hasAttribute('aria-disabled');
                    if (!isAvailable) {
                        alert('This item is currently unavailable.');
                        return;
                    }
                }

                const formData = new URLSearchParams({
                    action: 'add',
                    item_id: itemId,
                    quantity: quantity
                });

                fetch(api, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok && data.cart) {
                        updateCartUI(data.cart);
                    } else {
                        alert('Failed to add item: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Add to cart error:', err);
                    alert('Failed to add item to cart. Please try again.');
                });
            }

            function updateCartItem(itemId, quantity) {
                const formData = new URLSearchParams({
                    action: 'update',
                    item_id: itemId,
                    quantity: quantity
                });

                fetch(api, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok && data.cart) {
                        updateCartUI(data.cart);
                    }
                })
                .catch(err => console.error('Update cart error:', err));
            }

            function removeFromCart(itemId) {
                const formData = new URLSearchParams({
                    action: 'remove',
                    item_id: itemId
                });

                fetch(api, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok && data.cart) {
                        updateCartUI(data.cart);
                    }
                })
                .catch(err => console.error('Remove from cart error:', err));
            }

            // Product card interactions
            document.querySelectorAll('.product-card').forEach(card => {
                const qtyBox = card.querySelector('[data-qty]');
                const dec = card.querySelector('[data-dec]');
                const inc = card.querySelector('[data-inc]');
                const addBtn = card.querySelector('[data-add]');
                const span = qtyBox?.querySelector('.qty-value');
                const itemId = card.dataset.id;

                dec?.addEventListener('click', () => {
                    const current = parseInt(span.textContent || '1', 10);
                    span.textContent = Math.max(1, current - 1);
                });

                inc?.addEventListener('click', () => {
                    const current = parseInt(span.textContent || '1', 10);
                    span.textContent = current + 1;
                });

                addBtn?.addEventListener('click', () => {
                    const card = addBtn.closest('.product-card');
                    const isAvailable = card?.dataset.available !== '0' && !card?.hasAttribute('aria-disabled');
                    if (!isAvailable) {
                        alert('This item is currently unavailable.');
                        return;
                    }
                    const qty = parseInt(span.textContent || '1', 10);
                    if (itemId) {
                        addToCart(itemId, qty);
                    }
                });
            });

            // Cart interactions
            miniLines.addEventListener('click', (e) => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const itemId = li.dataset.id;

                if (e.target.matches('[data-inc]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const qtySpan = li.querySelector('span[data-qty]');
                    const currentQty = parseInt(qtySpan?.textContent || '0', 10);
                    if (currentQty > 0) {
                        updateCartItem(itemId, currentQty + 1);
                    }
                } else if (e.target.matches('[data-dec]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const qtySpan = li.querySelector('span[data-qty]');
                    const currentQty = parseInt(qtySpan?.textContent || '0', 10);
                    if (currentQty > 1) {
                        updateCartItem(itemId, currentQty - 1);
                    } else if (currentQty === 1) {
                        removeFromCart(itemId);
                    }
                } else if (e.target.matches('[data-remove]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    removeFromCart(itemId);
                }
            });

            // Cart toggle
            fabBtn?.addEventListener('click', () => {
                mini.classList.toggle('open');
            });

            document.getElementById('miniCartClose')?.addEventListener('click', () => {
                mini?.classList.remove('has-checkout-fields');
                if (checkoutFields) {
                    checkoutFields.style.display = 'none';
                    checkoutBtn.textContent = 'Checkout';
                }
                mini.classList.remove('open');
            });

            // Category tabs
            if (categoryTabs) {
                categoryTabs.addEventListener('click', (e) => {
                    if (e.target.classList.contains('category-tab')) {
                        document.querySelectorAll('.category-tab').forEach(tab => tab.classList.remove('active'));
                        e.target.classList.add('active');
                        
                        const category = e.target.dataset.category;
                        const cards = document.querySelectorAll('.product-card');
                        cards.forEach(card => {
                            if (category === 'all' || card.dataset.category === category) {
                                card.style.display = '';
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    }
                });
            }

            // Filters
            const filters = document.getElementById('menuFilters');
            const grid = document.getElementById('productGrid');
            if (filters && grid) {
                const applyFilters = () => {
                    const q = (filters.querySelector('[name="query"]')?.value || '').toLowerCase().trim();
                    const cat = filters.querySelector('[name="category"]')?.value || '';
                    const max = filters.querySelector('[name="max_price"]')?.value || '';
                    const cards = Array.from(grid.querySelectorAll('.product-card'));
                    cards.forEach(card => {
                        const name = (card.dataset.name || '');
                        const price = parseFloat(card.dataset.price || '0');
                        const catOk = !cat || card.dataset.category === cat;
                        const qOk = !q || name.includes(q);
                        const pOk = !max || price <= parseFloat(max);
                        card.style.display = (catOk && qOk && pOk) ? '' : 'none';
                    });
                };
                filters.addEventListener('input', applyFilters);
                filters.addEventListener('reset', () => setTimeout(applyFilters, 0));
            }

            // Availability check
            const availabilityApi = "<?= base_url('order/availability'); ?>";
            const itemIds = Array.from(grid.querySelectorAll('.product-card')).map(card => card.dataset.id).filter(Boolean);
            if (itemIds.length) {
                const params = new URLSearchParams();
                itemIds.forEach(id => params.append('ids[]', id));
                fetch(availabilityApi + '?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok && data.availability) {
                            Object.entries(data.availability).forEach(([id, available]) => {
                                const card = grid.querySelector(`[data-id="${id}"]`);
                                if (card && !available) {
                                    card.setAttribute('aria-disabled', 'true');
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Availability check error:', err));
            }

            // Pre-fill user information
            function fillUserInfo() {
                if (userInfo && Object.keys(userInfo).length > 0) {
                    const nameField = checkoutFields?.querySelector('[name="guest_name"]');
                    const phoneField = checkoutFields?.querySelector('[name="guest_phone"]');
                    const emailField = checkoutFields?.querySelector('[name="guest_email"]');
                    const mpesaPhoneInput = mpesaPhoneField?.querySelector('[name="mpesa_phone"]');
                    
                    if (nameField && userInfo.guest_name) nameField.value = userInfo.guest_name;
                    if (phoneField && userInfo.guest_phone) {
                        phoneField.value = userInfo.guest_phone;
                        if (mpesaPhoneInput) mpesaPhoneInput.value = userInfo.guest_phone;
                    }
                    if (emailField && userInfo.guest_email) emailField.value = userInfo.guest_email;
                }
            }

            // Checkout form
            checkoutBtn?.addEventListener('click', () => {
                if (checkoutFields.style.display === 'none' || !checkoutFields.style.display) {
                    checkoutFields.style.display = 'grid';
                    checkoutBtn.textContent = 'Place Order';
                    mini?.classList.add('has-checkout-fields');
                    fillUserInfo();
                } else {
                    const name = checkoutFields.querySelector('[name="guest_name"]').value.trim();
                    const phone = checkoutFields.querySelector('[name="guest_phone"]').value.trim();
                    const paymentMethod = paymentMethodSelect.value;
                    
                    if (!name || !phone) {
                        alert('Please fill in your name and phone number.');
                        return;
                    }
                    
                    if (paymentMethod === 'mpesa') {
                        const mpesaPhone = checkoutFields.querySelector('[name="mpesa_phone"]').value.trim();
                        if (!mpesaPhone) {
                            alert('Please enter your M-Pesa phone number.');
                            return;
                        }
                    }
                    
                    if (serviceTypeSelect.value === 'room_service') {
                        const roomNumber = checkoutFields.querySelector('[name="room_number"]').value.trim();
                        if (!roomNumber) {
                            alert('Please enter your room number for room service.');
                            return;
                        }
                    }
                    
                    // Disable button and show loading state
                    checkoutBtn.disabled = true;
                    checkoutBtn.textContent = 'Processing...';
                    
                    // Submit form - it will redirect to payment waiting page for M-Pesa
                    checkoutForm.submit();
                }
            });

            serviceTypeSelect?.addEventListener('change', (e) => {
                if (e.target.value === 'room_service') {
                    roomNumberField.style.display = 'block';
                    roomNumberField.querySelector('input').required = true;
                } else {
                    roomNumberField.style.display = 'none';
                    roomNumberField.querySelector('input').required = false;
                }
            });

            paymentMethodSelect?.addEventListener('change', (e) => {
                if (e.target.value === 'mpesa') {
                    mpesaPhoneField.style.display = 'block';
                    mpesaPhoneField.querySelector('input').required = true;
                } else {
                    mpesaPhoneField.style.display = 'none';
                    mpesaPhoneField.querySelector('input').required = false;
                }
            });

            loadCart();
        })();
    </script>
    <?php
    return ob_get_clean();
};
$pageTitle = 'Dine & Drink | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');