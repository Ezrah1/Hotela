<?php
$website = $website ?? settings('website', []);
$slot = function () use ($categories, $website) {
    ob_start(); ?>
    <section class="page-hero">
        <div class="container">
            <h1>Food & Drinks</h1>
            <p><?= htmlspecialchars($website['food_intro'] ?? 'Browse the menu, add to cart, and checkout.'); ?></p>
            <form id="menuFilters" class="room-filter-panel">
                <div class="room-filter-grid">
                    <label>
                        <span>Search</span>
                        <input type="text" name="query" placeholder="e.g. wings, soda, coffee">
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="category">
                            <option value="">All</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars(strtolower($category['name'])); ?>">
                                    <?= htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Max Price</span>
                        <input type="number" name="max_price" min="0" placeholder="e.g. 1500">
                    </label>
                </div>
                <div class="room-filter-actions">
                    <button type="reset" class="btn btn-ghost btn-small">Clear</button>
                </div>
            </form>
        </div>
    </section>

    <section class="container" id="menu">
        <style>
            /* Minimal, mobile-first product grid */
            .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
            .product-card { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: 0.9rem; overflow: hidden; display: grid; grid-template-rows: 150px auto; }
            .product-media { background: #fafaf9; display: flex; align-items: center; justify-content: center; }
            .product-media img { width: 100%; height: 150px; object-fit: cover; }
            .fa-product-icon { font-size: 56px; color: rgba(15,23,42,.35); }
            .product-body { padding: 0.85rem; display: grid; gap: 0.4rem; }
            .product-title { font-weight: 700; color: #0f172a; }
            .product-desc { color: #64748b; font-size: .9rem; line-height: 1.35; min-height: 2.4em; }
            .product-foot { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
            .product-price { font-weight: 800; color: #0f172a; }
            .qty { display: inline-flex; align-items: center; border: 1px solid rgba(15,23,42,.12); border-radius: 999px; padding: .15rem; }
            .qty button { background: transparent; border: 0; width: 28px; height: 28px; border-radius: 999px; cursor: pointer; }
            .qty span { min-width: 22px; text-align: center; font-weight: 700; }
            .product-card[aria-disabled="true"] { opacity: .5; pointer-events: none; position: relative; }
            .product-card[aria-disabled="true"]::after { content: 'Unavailable'; position: absolute; top: 8px; left: 8px; background: #fee2e2; color: #991b1b; font-size: .75rem; padding: .1rem .4rem; border-radius: 4px; }
            /* Floating cart */
            .cart-fab { position: fixed; right: 1rem; bottom: 1rem; z-index: 60; display:none; }
            .cart-fab > button { border-radius: 999px; padding: .6rem .9rem; background: var(--primary); color: #fff; border: none; box-shadow: 0 12px 30px rgba(0,0,0,.15); }
            .cart-fab__count { background: #fff; color: var(--primary); border-radius: 999px; padding: .1rem .5rem; margin-left: .5rem; font-weight: 700; }
            .mini-cart { position: fixed; right: 1rem; bottom: 4rem; width: min(92vw, 360px); max-height: 60vh; overflow: auto; background: #fff; border: 1px solid rgba(15,23,42,.1); border-radius: 1rem; box-shadow: 0 20px 50px rgba(0,0,0,.15); display:none; }
            .mini-cart.is-open { display:block; }
            .mini-cart header, .mini-cart footer { padding: .75rem 1rem; border-bottom: 1px solid rgba(15,23,42,.08); display:flex; justify-content: space-between; align-items: center; }
            .mini-cart footer { border-top: 1px solid rgba(15,23,42,.08); border-bottom: 0; }
            .mini-cart ul { list-style: none; padding: .5rem 1rem; margin: 0; display: grid; gap: .5rem; }
            .mini-cart li { display:flex; justify-content: space-between; align-items:center; gap:.5rem; }
            .fa-product-icon { font-size: 56px; color: rgba(15,23,42,.35); }
            .emoji-fallback { font-size: 48px; line-height: 1; }
        </style>

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
        ?>
        <div class="product-grid" id="productGrid">
            <?php foreach ($categories as $category): ?>
                <?php foreach ($category['items'] as $item): ?>
                    <?php
                    // Repository returns keys: item_id, item_name, price, sku
                    $title = $item['item_name'] ?? ($item['name'] ?? 'Menu Item');
                    $price = (float)($item['price'] ?? 0);
                    $skuVal = $item['sku'] ?? '';
                    $desc = !empty($item['description']) ? $item['description'] : ($skuVal ? 'SKU: ' . $skuVal : '');
                    $photo = $item['photo_url'] ?? null;
                    $key = strtolower($category['name']);
                    $fa = $categoryIconClasses[$key] ?? $categoryIconClasses[strtolower(trim($key))] ?? $categoryIconClasses[str_replace('-', ' ', $key)] ?? null;
                    if (!$photo) {
                        $photo = $categoryIcons[$key] ?? null;
                    }
                    $search = strtolower($title . ' ' . $desc . ' ' . ($skuVal ?: '') . ' ' . ($category['name'] ?? ''));
                    ?>
                    <article class="product-card" data-id="<?= (int)($item['item_id'] ?? $item['id'] ?? 0); ?>" data-name="<?= htmlspecialchars($search); ?>" data-price="<?= $price; ?>" data-category="<?= htmlspecialchars(strtolower($category['name'])); ?>">
                        <div class="product-media">
                            <?php if ($photo): ?>
                                <img src="<?= asset($photo); ?>" alt="<?= htmlspecialchars($title); ?>">
                            <?php elseif ($fa): ?>
                                <i class="<?= htmlspecialchars($fa); ?> fa-product-icon" aria-hidden="true"></i>
                                <span class="emoji-fallback" aria-hidden="true"><?= htmlspecialchars($categoryEmojis[$key] ?? '🍽️'); ?></span>
                            <?php else: ?>
                                <i class="fa-solid fa-utensils fa-product-icon" aria-hidden="true"></i>
                                <span class="emoji-fallback" aria-hidden="true"><?= htmlspecialchars($categoryEmojis[$key] ?? '🍽️'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <div class="product-title"><?= htmlspecialchars($title); ?></div>
                            <div class="product-desc"><?= htmlspecialchars($desc ?: ''); ?></div>
                            <div class="product-foot">
                                <span class="product-price">KES <?= number_format($price, 0); ?></span>
                                <div class="row-actions">
                                    <div class="qty" data-qty>
                                        <button type="button" aria-label="Decrease" data-dec>-</button>
                                        <span>1</span>
                                        <button type="button" aria-label="Increase" data-inc>+</button>
                                    </div>
                                    <button class="btn btn-outline btn-small" data-add>Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="cart-fab" id="cartFab"><button type="button" id="cartFabBtn">Cart <span class="cart-fab__count" id="cartFabCount">0</span></button></div>
    <div class="mini-cart" id="miniCart" aria-hidden="true">
        <header>
            <strong>My Order</strong>
            <button class="btn btn-ghost btn-small" id="miniCartClose">&times;</button>
        </header>
        <ul id="miniCartLines"></ul>
        <footer>
            <div class="row-actions" style="justify-content: space-between; width: 100%;">
                <strong id="miniCartTotal">KES 0</strong>
                <form id="checkoutForm" method="post" action="<?= base_url('order/checkout'); ?>">
                    <input type="hidden" name="payment_method" value="cash">
                    <button class="btn btn-primary btn-small" type="submit">Checkout</button>
                </form>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = document.getElementById('menuFilters');
            const grid = document.getElementById('productGrid');
            const api = "<?= base_url('order/cart'); ?>";
            const availabilityApi = "<?= base_url('order/availability'); ?>";
            const fab = document.getElementById('cartFab');
            const fabBtn = document.getElementById('cartFabBtn');
            const fabCount = document.getElementById('cartFabCount');
            const mini = document.getElementById('miniCart');
            const miniLines = document.getElementById('miniCartLines');
            const miniTotal = document.getElementById('miniCartTotal');
            const checkoutForm = document.getElementById('checkoutForm');

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
            filters?.addEventListener('input', applyFilters);
            filters?.addEventListener('reset', () => setTimeout(applyFilters, 0));
            applyFilters();

            function formatKES(v){ return 'KES ' + Number(v||0).toLocaleString(undefined, {minimumFractionDigits: 0}); }
            function drawCart(data){
                const lines = (data.cart && data.cart.lines) ? data.cart.lines : [];
                const total = (data.cart && data.cart.total) ? data.cart.total : 0;
                miniLines.innerHTML = lines.length ? lines.map(l => `
                    <li data-id="${l.id}">
                        <div><strong>${l.name}</strong><br><small>${formatKES(l.price)}</small></div>
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-small" data-dec>-</button>
                            <span>${l.qty}</span>
                            <button class="btn btn-ghost btn-small" data-inc>+</button>
                            <button class="btn btn-outline btn-small" data-remove>&times;</button>
                        </div>
                    </li>
                `).join('') : '<li class="empty-state">Your cart is empty.</li>';
                miniTotal.textContent = formatKES(total);
                const count = lines.reduce((a,b)=>a + (b.qty||0), 0);
                fabCount.textContent = String(count);
                fab.style.display = count > 0 ? 'block' : 'none';
            }
            function refreshCart(){ fetch(api).then(r=>r.json()).then(drawCart); }
            refreshCart();

            grid.querySelectorAll('.product-card').forEach(card => {
                const qtyBox = card.querySelector('[data-qty]');
                const dec = card.querySelector('[data-dec]');
                const inc = card.querySelector('[data-inc]');
                const span = qtyBox?.querySelector('span');
                dec?.addEventListener('click', () => {
                    span.textContent = String(Math.max(1, (parseInt(span.textContent||'1',10)||1)-1));
                });
                inc?.addEventListener('click', () => {
                    span.textContent = String((parseInt(span.textContent||'1',10)||1)+1);
                });
                card.querySelector('[data-add]')?.addEventListener('click', () => {
                    const id = card.dataset.id;
                    const q = parseInt(span?.textContent||'1', 10) || 1;
                    fetch(api, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body: new URLSearchParams({action:'add', item_id:id, quantity:q}) })
                        .then(r=>r.json()).then((d)=>{ drawCart(d); mini.classList.add('is-open'); mini.setAttribute('aria-hidden','false'); })
                        .catch(()=>alert('Unable to add to cart. Please try again.'));
                });
            });

            miniLines.addEventListener('click', e => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const id = li.dataset.id;
                if (e.target.matches('[data-inc]')) {
                    changeQty(id, 1);
                } else if (e.target.matches('[data-dec]')) {
                    changeQty(id, -1);
                } else if (e.target.matches('[data-remove]')) {
                    fetch(api, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'remove', item_id:id}) })
                        .then(r=>r.json()).then(drawCart);
                }
            });
            function changeQty(id, delta) {
                fetch(api).then(r=>r.json()).then(data => {
                    const line = (data.cart.lines||[]).find(x => String(x.id)===String(id));
                    const next = Math.max(0, (line ? line.qty : 0) + delta);
                    fetch(api, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({action:'update', item_id:id, quantity:next}) })
                        .then(r=>r.json()).then(drawCart);
                });
            }

            fabBtn?.addEventListener('click', () => { mini.classList.toggle('is-open'); mini.setAttribute('aria-hidden', mini.classList.contains('is-open')?'false':'true'); });
            document.getElementById('miniCartClose')?.addEventListener('click', () => { mini.classList.remove('is-open'); mini.setAttribute('aria-hidden','true'); });

            // availability
            const ids = Array.from(grid.querySelectorAll('.product-card')).map(n=>n.dataset.id);
            if (ids.length) {
                fetch("<?= base_url('order/availability'); ?>" + '?' + new URLSearchParams(ids.map(id=>['ids[]',id])))
                    .then(r=>r.json()).then(data => {
                        const map = (data && data.availability) ? data.availability : {};
                        grid.querySelectorAll('.product-card').forEach(c => {
                            if (map[c.dataset.id] === false) {
                                c.setAttribute('aria-disabled','true');
                            }
                        });
                    });
            }
        });
    </script>
    <?php
    return ob_get_clean();
};
$pageTitle = 'Food & Drinks | ' . settings('branding.name', 'Hotela');
include view_path('layouts/public.php');


