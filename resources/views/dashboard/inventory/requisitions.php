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
            <p class="eyebrow">Automatic & Manual Requisitions • Ops Verification → Finance Approval → Supplier Order → Receipt</p>
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
        <form class="folio-form" method="post" action="<?= base_url('staff/dashboard/inventory/requisitions'); ?>" id="req-form">
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

            <label>
                <span>Urgency</span>
                <select name="urgency">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </label>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Submit Requisition</button>
            </div>
        </form>
    </div>

<section class="card tab-panel" data-panel="queue">
    <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <h3 style="margin: 0;">Requisition Queue</h3>
        <div style="display: flex; gap: 0.5rem; margin-left: auto;">
            <select id="filter-type" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                <option value="">All Types</option>
                <option value="auto" <?= ($filters['type'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto</option>
                <option value="staff" <?= ($filters['type'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                <option value="internal" <?= ($filters['type'] ?? '') === 'internal' ? 'selected' : ''; ?>>Internal</option>
                <option value="procurement" <?= ($filters['type'] ?? '') === 'procurement' ? 'selected' : ''; ?>>Procurement</option>
            </select>
            <select id="filter-status" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="ordered" <?= ($filters['status'] ?? '') === 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : ''; ?>>Received</option>
            </select>
            <button onclick="applyFilters()" class="btn btn-outline" style="padding: 0.5rem 1rem;">Filter</button>
        </div>
    </div>

    <?php if (empty($requisitions)): ?>
        <p class="muted">No requisitions found.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table-lite" style="min-width: 1000px;">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Requested By</th>
                    <th>Items</th>
                    <th>Urgency</th>
                    <th>Workflow Status</th>
                    <th>Cost</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php 
                $userRole = $userRole ?? '';
                $supplierRepo = new \App\Repositories\SupplierRepository();
                $allSuppliers = $supplierRepo->all();
                foreach ($requisitions as $req): 
                    $type = strtolower($req['type'] ?? 'staff');
                    $status = strtolower($req['status'] ?? 'pending');
                    $urgency = strtolower($req['urgency'] ?? 'medium');
                    $opsVerified = (bool)($req['ops_verified'] ?? false);
                    $financeApproved = (bool)($req['finance_approved'] ?? false);
                ?>
                    <tr>
                        <td>
                            <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem;">
                                <?= htmlspecialchars($req['reference']); ?>
                            </code>
                            <?php if ($type === 'auto'): ?>
                                <br><small style="color: #64748b;">Auto-generated</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $typeLabels = [
                                'auto' => ['label' => 'Auto', 'color' => '#3b82f6'],
                                'staff' => ['label' => 'Staff', 'color' => '#8b5cf6'],
                                'internal' => ['label' => 'Internal', 'color' => '#10b981'],
                                'procurement' => ['label' => 'Procurement', 'color' => '#f59e0b'],
                            ];
                            $typeInfo = $typeLabels[$type] ?? $typeLabels['staff'];
                            ?>
                            <span style="background: <?= $typeInfo['color']; ?>; color: #fff; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">
                                <?= htmlspecialchars($typeInfo['label']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($req['requester'] ?? 'System'); ?></td>
                        <td>
                            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem;">
                                <?php
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
                        <td>
                            <?php
                            $urgencyColors = [
                                'urgent' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                'high' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                'medium' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                'low' => ['bg' => '#f3f4f6', 'text' => '#374151'],
                            ];
                            $urgencyColor = $urgencyColors[$urgency] ?? $urgencyColors['medium'];
                            ?>
                            <span style="background: <?= $urgencyColor['bg']; ?>; color: <?= $urgencyColor['text']; ?>; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: capitalize;">
                                <?= htmlspecialchars($urgency); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem;">
                                <span style="color: #64748b;">Status: <strong><?= htmlspecialchars(ucfirst($status)); ?></strong></span>
                                <?php if ($opsVerified): ?>
                                    <span style="color: #059669;">✓ Ops Verified</span>
                                    <?php if ($req['ops_verified_by_name']): ?>
                                        <small style="color: #94a3b8;">by <?= htmlspecialchars($req['ops_verified_by_name']); ?></small>
                                    <?php endif; ?>
                                <?php elseif (in_array($userRole, ['admin', 'operation_manager', 'director'], true) && $status === 'pending'): ?>
                                    <span style="color: #f59e0b;">⏳ Needs Ops Review</span>
                                <?php endif; ?>
                                <?php if ($financeApproved): ?>
                                    <span style="color: #059669;">✓ Finance Approved</span>
                                    <?php if ($req['finance_approved_by_name']): ?>
                                        <small style="color: #94a3b8;">by <?= htmlspecialchars($req['finance_approved_by_name']); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($opsVerified && in_array($userRole, ['admin', 'finance_manager', 'director'], true) && $status === 'pending'): ?>
                                    <span style="color: #f59e0b;">⏳ Needs Finance Review</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($req['cost_estimate']) && (float)$req['cost_estimate'] > 0): ?>
                                <strong style="color: #059669;">KES <?= number_format((float)$req['cost_estimate'], 2); ?></strong>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($req['supplier_name'])): ?>
                                <?= htmlspecialchars($req['supplier_name']); ?>
                            <?php elseif (!empty($req['items'][0]['preferred_supplier_name'])): ?>
                                <small style="color: #64748b;">Preferred: <?= htmlspecialchars($req['items'][0]['preferred_supplier_name']); ?></small>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; min-width: 200px;">
                                <?php
                                // Ops Verification Actions
                                if (!$opsVerified && in_array($userRole, ['admin', 'operation_manager', 'director'], true) && $status === 'pending'):
                                ?>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/ops-verify'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <textarea name="ops_notes" placeholder="Ops notes..." rows="2" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;" required></textarea>
                                        <input type="number" name="cost_estimate" step="0.01" placeholder="Cost Estimate (KES)" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;">
                                        <button class="btn btn-primary btn-small" type="submit">Verify & Forward to Finance</button>
                                    </form>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/ops-verify'); ?>">
                                        <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <textarea name="ops_notes" placeholder="Rejection reason..." rows="2" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem; width: 100%;" required></textarea>
                                        <button class="btn btn-outline btn-small" type="submit" style="margin-top: 0.25rem; width: 100%;">Reject</button>
                                    </form>
                                <?php
                                // Finance Approval Actions
                                elseif ($opsVerified && !$financeApproved && in_array($userRole, ['admin', 'finance_manager', 'director'], true) && $status === 'pending'):
                                ?>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/finance-approve'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <textarea name="finance_notes" placeholder="Finance notes..." rows="2" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;"></textarea>
                                        <button class="btn btn-primary btn-small" type="submit">Approve</button>
                                    </form>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/finance-approve'); ?>">
                                        <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <textarea name="finance_notes" placeholder="Rejection reason..." rows="2" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem; width: 100%;" required></textarea>
                                        <button class="btn btn-outline btn-small" type="submit" style="margin-top: 0.25rem; width: 100%;">Reject</button>
                                    </form>
                                <?php
                                // Supplier Assignment & PO Creation
                                elseif ($financeApproved && $status === 'approved' && in_array($userRole, ['admin', 'operation_manager', 'finance_manager', 'director'], true)):
                                ?>
                                    <?php if (empty($req['supplier_id'])): ?>
                                        <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/assign-supplier'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                            <select name="supplier_id" required style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;">
                                                <option value="">Select Supplier</option>
                                                <?php foreach ($allSuppliers as $supplier): ?>
                                                    <option value="<?= (int)$supplier['id']; ?>" <?= ($req['items'][0]['preferred_supplier_id'] ?? null) == $supplier['id'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($supplier['name']); ?>
                                                        <?= ($req['items'][0]['preferred_supplier_id'] ?? null) == $supplier['id'] ? '(Preferred)' : ''; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-primary btn-small" type="submit">Assign Supplier</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/create-po'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                            <input type="hidden" name="supplier_id" value="<?= (int)$req['supplier_id']; ?>">
                                            <input type="date" name="expected_date" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;">
                                            <button class="btn btn-primary btn-small" type="submit">Create Purchase Order</button>
                                        </form>
                                    <?php endif; ?>
                                <?php
                                // PO Received Actions
                                elseif (!empty($req['purchase_order']) && $req['purchase_order']['status'] === 'sent' && in_array($userRole, ['admin', 'operation_manager'], true)):
                                ?>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/purchase-orders/receive'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <input type="hidden" name="purchase_order_id" value="<?= (int)$req['purchase_order']['id']; ?>">
                                        <select name="location_id" required style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;">
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary btn-small" type="submit">Mark Received</button>
                                    </form>
                                <?php
                                // Internal Release (for internal requisitions with sufficient stock)
                                elseif ($financeApproved && $type === 'internal' && $status === 'approved' && in_array($userRole, ['admin', 'operation_manager'], true)):
                                ?>
                                    <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/complete'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <input type="hidden" name="requisition_id" value="<?= (int)$req['id']; ?>">
                                        <select name="location_id" required style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;">
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?= (int)$location['id']; ?>"><?= htmlspecialchars($location['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary btn-small" type="submit">Release Items</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.875rem;">No actions available</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<script>
function applyFilters() {
    const type = document.getElementById('filter-type').value;
    const status = document.getElementById('filter-status').value;
    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (status) params.set('status', status);
    window.location.href = '<?= base_url('staff/dashboard/inventory/requisitions'); ?>' + (params.toString() ? '?' + params.toString() : '');
}
</script>

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

