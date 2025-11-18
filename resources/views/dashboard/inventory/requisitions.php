<?php
$pageTitle = 'Inventory Requisitions | Hotela';
$requisitions = $requisitions ?? [];
$inventoryItems = $inventoryItems ?? [];
$locations = $locations ?? [];

ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Requisitions</h2>
            <p class="eyebrow">Internal cart for staff requests • Approval → Release → Complete</p>
        </div>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
        <?php elseif (!empty($_GET['success'])): ?>
            <div class="alert success">Action completed.</div>
        <?php endif; ?>
    </header>

    <div class="tabs">
        <button class="tab is-active" data-tab="create">Create Request</button>
        <button class="tab" data-tab="queue">Queue</button>
        <button class="tab" data-tab="mine">My Requests</button>
        <button class="tab" data-tab="history">History</button>
    </div>

    <div class="tab-panel is-active" data-panel="create">
        <form class="folio-form" method="post" action="<?= base_url('dashboard/inventory/requisitions'); ?>" id="req-form">
            <div class="form-grid">
                <label>
                    <span>Item</span>
                    <select id="req-item">
                        <option value="">Select item</option>
                        <?php foreach ($inventoryItems as $item): ?>
                            <option value="<?= (int)$item['id']; ?>"><?= htmlspecialchars($item['name']); ?> (<?= htmlspecialchars($item['sku']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Quantity</span>
                    <input type="number" step="0.01" id="req-qty" placeholder="e.g. 2">
                </label>
                <div class="align-end">
                    <button class="btn btn-outline" type="button" id="add-to-cart">Add to Cart</button>
                </div>
            </div>

            <div class="cart card" id="req-cart" style="margin-top:1rem;">
                <h3>Request Cart</h3>
                <table class="table-lite">
                    <thead>
                    <tr><th>Item</th><th>Qty</th><th></th></tr>
                    </thead>
                    <tbody id="cart-body">
                    <tr class="empty"><td colspan="3">No items yet.</td></tr>
                    </tbody>
                </table>
            </div>

            <label>
                <span>Note</span>
                <input type="text" name="notes" placeholder="e.g. Room 204 cleaning / Urgent kitchen supply">
            </label>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Submit Requisition</button>
            </div>
        </form>
    </div>

<section class="card tab-panel" data-panel="queue">
    <h3>Requisition Queue</h3>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Requested By</th>
            <th>Items</th>
            <th>Status</th>
            <th>PO</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($requisitions as $req): ?>
            <tr>
                <td><?= htmlspecialchars($req['reference']); ?></td>
                <td><?= htmlspecialchars($req['requester'] ?? ''); ?></td>
                <td>
                    <ul>
						<?php
						// Aggregate duplicate items for a cleaner display
						$grouped = [];
						foreach ($req['items'] as $item) {
							$key = ($item['name'] ?? '') . '|' . ($item['unit'] ?? '');
							$qty = (float)($item['quantity'] ?? 0);
							if (!isset($grouped[$key])) {
								$grouped[$key] = [
									'name' => (string)($item['name'] ?? ''),
									'unit' => (string)($item['unit'] ?? ''),
									'quantity' => 0.0,
								];
							}
							$grouped[$key]['quantity'] += $qty;
						}
						foreach ($grouped as $g):
						?>
							<li><?= htmlspecialchars($g['name']); ?> — <?= htmlspecialchars(number_format((float)$g['quantity'], 3)); ?> <?= htmlspecialchars($g['unit']); ?></li>
						<?php endforeach; ?>
                    </ul>
                </td>
                <td><?= htmlspecialchars(ucfirst($req['status'])); ?></td>
                <td>
                    <?php if (!empty($req['purchase_order'])): ?>
                        <?= htmlspecialchars($req['purchase_order']['supplier_name']); ?> (<?= htmlspecialchars($req['purchase_order']['status']); ?>)
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($req['status'] === 'pending'): ?>
                        <form method="post" action="<?= base_url('dashboard/inventory/requisitions/status'); ?>" class="inline-form">
                            <input type="hidden" name="requisition_id" value="<?= (int)$req['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button class="btn btn-outline btn-small" type="submit">Reject</button>
                        </form>
                        <form method="post" action="<?= base_url('dashboard/inventory/requisitions/status'); ?>" class="inline-form">
                            <input type="hidden" name="requisition_id" value="<?= (int)$req['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <input type="text" name="supplier_name" placeholder="Supplier" required>
                            <button class="btn btn-primary btn-small" type="submit">Approve & Issue PO</button>
                        </form>
                        <div class="hint">Or approve for release without PO:</div>
                        <form method="post" action="<?= base_url('dashboard/inventory/requisitions/complete'); ?>" class="inline-form">
                            <input type="hidden" name="requisition_id" value="<?= (int)$req['id']; ?>">
                            <select name="location_id" required>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline btn-small" type="submit">Approve & Release</button>
                        </form>
                    <?php elseif (!empty($req['purchase_order']) && $req['purchase_order']['status'] === 'sent'): ?>
                        <form method="post" action="<?= base_url('dashboard/inventory/purchase-orders/receive'); ?>" class="inline-form">
                            <input type="hidden" name="purchase_order_id" value="<?= (int)$req['purchase_order']['id']; ?>">
                            <select name="location_id" required>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary btn-small" type="submit">Mark Received</button>
                        </form>
                    <?php elseif ($req['status'] === 'approved'): ?>
                        <form method="post" action="<?= base_url('dashboard/inventory/requisitions/complete'); ?>" class="inline-form">
                            <input type="hidden" name="requisition_id" value="<?= (int)$req['id']; ?>">
                            <select name="location_id" required>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary btn-small" type="submit">Release Items</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card tab-panel" data-panel="mine" style="display:none;">
    <h3>My Requests</h3>
    <p class="muted">Coming soon.</p>
</section>

<section class="card tab-panel" data-panel="history" style="display:none;">
    <h3>History</h3>
    <p class="muted">Coming soon.</p>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = Array.from(document.querySelectorAll('.tabs .tab'));
    const panels = Array.from(document.querySelectorAll('.tab-panel'));
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.toggle('is-active', t === tab));
            panels.forEach(p => {
                const active = p.dataset.panel === target || (p.dataset.panel === 'queue' && target === 'queue');
                p.classList.toggle('is-active', active);
                p.style.display = active ? '' : 'none';
            });
        });
    });

    const cartBody = document.getElementById('cart-body');
    const addBtn = document.getElementById('add-to-cart');
    const itemSel = document.getElementById('req-item');
    const qtyInp = document.getElementById('req-qty');

    function syncEmpty() {
        const empty = cartBody.querySelector('.empty');
        const hasRows = cartBody.querySelectorAll('tr[data-id]').length > 0;
        if (empty) empty.style.display = hasRows ? 'none' : '';
    }

    addBtn?.addEventListener('click', () => {
        const id = parseInt(itemSel.value || '0', 10);
        const name = itemSel.options[itemSel.selectedIndex]?.text || '';
        const qty = parseFloat(qtyInp.value || '0');
        if (!id || qty <= 0) return;

        const existing = cartBody.querySelector(`tr[data-id="${id}"]`);
        if (existing) {
            const qtyCell = existing.querySelector('[data-qty]');
            const newQty = parseFloat(qtyCell.textContent) + qty;
            qtyCell.textContent = String(newQty);
            existing.querySelector('input[name="quantity[]"]').value = String(newQty);
        } else {
            const tr = document.createElement('tr');
            tr.dataset.id = String(id);
            tr.innerHTML = `
                <td>${name}<input type="hidden" name="inventory_item_id[]" value="${id}"></td>
                <td data-qty>${qty}</td>
                <td><button type="button" class="btn btn-ghost btn-small" data-remove>&times;</button>
                    <input type="hidden" name="quantity[]" value="${qty}"></td>
            `;
            cartBody.appendChild(tr);
            tr.querySelector('[data-remove]')?.addEventListener('click', () => {
                tr.remove();
                syncEmpty();
            });
        }
        syncEmpty();
        qtyInp.value = '';
    });
    syncEmpty();
});
</script>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

