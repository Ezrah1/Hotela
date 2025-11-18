<?php
$pageTitle = 'Point of Sale | Hotela';
$categories = $categories ?? [];
$locations = $locations ?? [];
$taxRate = (float)settings('pos.tax_rate', 0.00);
$user = $user ?? \App\Support\Auth::user();
$categoryNames = array_map(fn ($category) => $category['name'], $categories);
ob_start();
?>
<section class="pos-shell card">
	<?php if (!empty($_GET['error'])): ?>
		<div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
	<?php elseif (!empty($_GET['success'])): ?>
		<div class="alert success">Sale recorded successfully.</div>
	<?php endif; ?>

	<div class="pos-items__search">
		<input type="search" id="pos-search" placeholder="Search item..." aria-label="Search products">
	</div>

	<div class="pos-layout" data-tax-rate="<?= htmlspecialchars($taxRate); ?>">
		<aside class="pos-categories" data-mobile-state="closed">
			<div class="pos-categories__header">
				<button type="button" class="pos-categories__toggle" id="pos-category-toggle">
					<span>Categories</span>
					<span>☰</span>
				</button>
			</div>
			<ul id="pos-category-list">
				<li><button type="button" class="active" data-category="all">All Items</button></li>
				<?php foreach ($categoryNames as $category): ?>
					<li>
						<button type="button" data-category="<?= htmlspecialchars($category); ?>">
							<?= htmlspecialchars($category); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>

		<section class="pos-items">
			<div class="pos-grid" id="pos-item-grid">
				<?php foreach ($categories as $category): ?>
					<?php foreach ($category['items'] as $item): ?>
						<?php
						$data = [
							'id' => (int)$item['id'],
							'name' => $item['name'],
							'price' => (float)($item['price'] ?? 0),
							'category' => $category['name'],
						];
						?>
						<button
							type="button"
							class="pos-card"
							data-category="<?= htmlspecialchars($category['name']); ?>"
							data-name="<?= htmlspecialchars(mb_strtolower($item['name'])); ?>"
							data-item='<?= json_encode($data); ?>'
						>
							<?php if (!empty($item['image'])): ?>
								<img src="<?= asset($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
							<?php else: ?>
								<div class="pos-card__placeholder"><?= strtoupper(substr($item['name'], 0, 2)); ?></div>
							<?php endif; ?>
							<div class="pos-card__meta">
								<strong><?= htmlspecialchars($item['name']); ?></strong>
								<span>KES <?= number_format((float)$item['price'], 2); ?></span>
							</div>
							<span class="pos-card__badge add">Add</span>
						</button>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="pos-cart" id="pos-cart">
			<header class="pos-cart__header">
				<div>
					<h3>Current Order</h3>
					<small id="pos-cart-count">0 items</small>
				</div>
				<div class="pos-cart__actions">
					<button type="button" class="btn btn-outline btn-small" id="clear-order">Clear</button>
					<button type="button" class="btn btn-outline btn-small pos-cart-close" id="cart-close">Close</button>
				</div>
			</header>

			<form method="post" action="<?= base_url('dashboard/pos/sale'); ?>" id="pos-form">
				<div class="pos-cart__lines" id="order-lines">
					<p class="muted text-center">No items yet. Tap a product to add.</p>
				</div>

				<div class="pos-cart__customer">
					<label>
						<span>Customer</span>
						<select name="customer_type" id="customer-type">
							<option value="walkin">Walk-in customer</option>
							<option value="guest">Checked-in guest</option>
						</select>
					</label>
					<label id="reservation-field" style="display:none;">
						<span>Select Guest</span>
						<select name="reservation_reference" id="reservation-reference">
							<option value="">Select a guest...</option>
							<?php foreach ($checkedInGuests ?? [] as $guest): ?>
								<option value="<?= htmlspecialchars($guest['reference']); ?>">
									<?= htmlspecialchars($guest['guest_name']); ?>
									<?php if (!empty($guest['room_number'])): ?>
										- Room <?= htmlspecialchars($guest['room_number']); ?>
									<?php elseif (!empty($guest['room_type_name'])): ?>
										- <?= htmlspecialchars($guest['room_type_name']); ?>
									<?php endif; ?>
									(<?= htmlspecialchars($guest['reference']); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<div class="pos-cart__summary">
					<div>
						<span>Subtotal</span>
						<strong id="order-subtotal">KES 0.00</strong>
					</div>
					<div>
						<span>Tax</span>
						<strong id="order-tax">KES 0.00</strong>
					</div>
					<div>
						<span>Total</span>
						<strong id="order-total">KES 0.00</strong>
					</div>
				</div>

				<div class="pos-cart__fields">
					<label>
						<span>Staff Taking Order</span>
						<input type="text" value="<?= htmlspecialchars($user['name'] ?? 'Staff'); ?>" readonly style="background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; border-radius: 0.5rem; cursor: not-allowed; font-size: 0.95rem; width: 100%;">
						<small style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem; display: block;">Order will be recorded under your name</small>
					</label>
					<label>
						<span>Inventory Location</span>
						<select name="location_id">
							<?php foreach ($locations as $location): ?>
								<option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span>Payment Type</span>
						<select name="payment_type" id="payment-type">
							<option value="cash">Cash</option>
							<option value="mpesa">M-Pesa</option>
							<option value="card">Card</option>
							<option value="room">Room Charge</option>
							<option value="corporate">Corporate</option>
						</select>
					</label>
					<label>
						<span>Order Notes</span>
						<input type="text" name="notes" placeholder="Optional note">
					</label>
				</div>

				<div id="order-inputs"></div>
				<button class="btn btn-primary btn-block" type="submit" id="complete-sale" disabled>Complete Sale</button>
			</form>
		</section>
	</div>
	<button type="button" class="pos-cart-floating" id="cart-open">View Cart</button>
</section>

<script>
const POS = (() => {
	const itemButtons = document.querySelectorAll('.pos-card');
	const categoryButtons = document.querySelectorAll('#pos-category-list button');
	const searchInput = document.getElementById('pos-search');
	const itemGrid = document.getElementById('pos-item-grid');
	const orderLines = document.getElementById('order-lines');
	const orderInputs = document.getElementById('order-inputs');
	const subtotalEl = document.getElementById('order-subtotal');
	const taxEl = document.getElementById('order-tax');
	const totalEl = document.getElementById('order-total');
	const cartCount = document.getElementById('pos-cart-count');
	const paymentTypeSelect = document.getElementById('payment-type');
	const reservationField = document.getElementById('reservation-field');
	const customerTypeSelect = document.getElementById('customer-type');
	const clearBtn = document.getElementById('clear-order');
	const cart = document.getElementById('pos-cart');
	const cartOpen = document.getElementById('cart-open');
	const cartClose = document.getElementById('cart-close');
	const taxRate = parseFloat(document.querySelector('.pos-layout').dataset.taxRate || '0');
	const completeBtn = document.getElementById('complete-sale');
	const categoryToggle = document.getElementById('pos-category-toggle');
	const categoriesWrapper = document.querySelector('.pos-categories');

	let order = [];
	let activeCategory = 'all';

	const formatMoney = (value) => 'KES ' + Number(value).toFixed(2);

	function renderItems() {
		const searchTerm = searchInput.value.trim().toLowerCase();
		itemButtons.forEach(btn => {
			const matchesCategory = activeCategory === 'all' || btn.dataset.category === activeCategory;
			const matchesSearch = btn.dataset.name.includes(searchTerm);
			btn.style.display = matchesCategory && matchesSearch ? 'flex' : 'none';
		});
	}

	function renderOrder() {
		orderLines.innerHTML = '';
		orderInputs.innerHTML = '';

		if (order.length === 0) {
			orderLines.innerHTML = '<p class="muted text-center">No items yet. Tap a product to add.</p>';
			subtotalEl.textContent = formatMoney(0);
			taxEl.textContent = formatMoney(0);
			totalEl.textContent = formatMoney(0);
			cartCount.textContent = '0 items';
			completeBtn.disabled = true;
			return;
		}

		completeBtn.disabled = false;
		const fragment = document.createDocumentFragment();
		let subtotal = 0;

		order.forEach((line, index) => {
			subtotal += line.price * line.quantity;
			const lineEl = document.createElement('div');
			lineEl.className = 'pos-line';
			lineEl.innerHTML = `
				<div>
					<strong>${line.name}</strong>
					<small>${formatMoney(line.price)}</small>
				</div>
				<div class="pos-line__qty">
					<button type="button" data-action="dec" data-index="${index}">-</button>
					<span>${line.quantity}</span>
					<button type="button" data-action="inc" data-index="${index}">+</button>
				</div>
				<div class="pos-line__total">
					<strong>${formatMoney(line.price * line.quantity)}</strong>
					<button type="button" data-remove="${index}" aria-label="Remove">✕</button>
				</div>
			`;
			fragment.appendChild(lineEl);

			const itemInput = document.createElement('input');
			itemInput.type = 'hidden';
			itemInput.name = 'item_ids[]';
			itemInput.value = line.id;

			const qtyInput = document.createElement('input');
			qtyInput.type = 'hidden';
			qtyInput.name = 'quantities[]';
			qtyInput.value = line.quantity;

			const priceInput = document.createElement('input');
			priceInput.type = 'hidden';
			priceInput.name = 'prices[]';
			priceInput.value = line.price;

			orderInputs.appendChild(itemInput);
			orderInputs.appendChild(qtyInput);
			orderInputs.appendChild(priceInput);
		});

		orderLines.appendChild(fragment);
		const tax = subtotal * taxRate;
		const total = subtotal + tax;

		subtotalEl.textContent = formatMoney(subtotal);
		taxEl.textContent = formatMoney(tax);
		totalEl.textContent = formatMoney(total);
		cartCount.textContent = `${order.length} item${order.length !== 1 ? 's' : ''}`;
	}

	function addItem(button) {
		const data = JSON.parse(button.dataset.item);
		const existing = order.find(line => line.id === data.id);
		if (existing) {
			existing.quantity += 1;
		} else {
			order.push({ ...data, quantity: 1 });
		}
		renderOrder();
		if (window.innerWidth < 900) {
			cart.classList.add('is-open');
		}
	}

	itemButtons.forEach(btn => {
		btn.addEventListener('click', (e) => {
			// Ensure all items can be clicked, even if marked as unavailable
			e.preventDefault();
			e.stopPropagation();
			addItem(btn);
		});
	});

	categoryButtons.forEach(btn => {
		btn.addEventListener('click', () => {
			categoryButtons.forEach(b => b.classList.remove('active'));
			btn.classList.add('active');
			activeCategory = btn.dataset.category;
			renderItems();
			if (window.innerWidth < 900) {
				categoriesWrapper.dataset.mobileState = 'closed';
			}
		});
	});

	searchInput.addEventListener('input', renderItems);

	orderLines.addEventListener('click', (event) => {
		const { action, index, remove } = event.target.dataset;
		if (action && index !== undefined) {
			const idx = parseInt(index, 10);
			if (action === 'inc') {
				order[idx].quantity += 1;
			} else if (action === 'dec' && order[idx].quantity > 1) {
				order[idx].quantity -= 1;
			}
			renderOrder();
		}
		if (remove !== undefined) {
			order.splice(parseInt(remove, 10), 1);
			renderOrder();
		}
	});

	paymentTypeSelect.addEventListener('change', () => toggleReservationField());
	customerTypeSelect.addEventListener('change', () => toggleReservationField());

	function toggleReservationField() {
		const needsReservation = paymentTypeSelect.value === 'room' || customerTypeSelect.value === 'guest';
		reservationField.style.display = needsReservation ? 'block' : 'none';
	}

	clearBtn.addEventListener('click', () => {
		order = [];
		renderOrder();
	});

	cartOpen?.addEventListener('click', () => cart.classList.add('is-open'));
	cartClose?.addEventListener('click', () => cart.classList.remove('is-open'));

	categoryToggle?.addEventListener('click', () => {
		const state = categoriesWrapper.dataset.mobileState === 'open' ? 'closed' : 'open';
		categoriesWrapper.dataset.mobileState = state;
	});

	renderItems();
	renderOrder();
})();
</script>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

