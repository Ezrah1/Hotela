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
            <div class="alert danger">
                <?= htmlspecialchars($_GET['error']); ?>
                <?php if (!empty($_GET['duplicate_id'])): ?>
                    <br><a href="<?= base_url('staff/dashboard/inventory/requisitions?filter=mine&highlight=' . (int)$_GET['duplicate_id']); ?>" style="color: #dc2626; text-decoration: underline; margin-top: 0.5rem; display: inline-block;">
                        View Existing Requisition
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($_GET['success'])): ?>
            <div class="alert success">Action completed.</div>
        <?php endif; ?>
    </header>

    <div class="tabs">
        <button class="tab is-active" data-tab="create">Create Request</button>
        <button class="tab" data-tab="queue">Queue</button>
        <button class="tab" data-tab="mine">My Requests</button>
        <?php if ($canViewAll ?? false): ?>
            <button class="tab" data-tab="all">All Requests</button>
        <?php else: ?>
            <button class="tab" data-tab="department">My Department</button>
        <?php endif; ?>
        <button class="tab" data-tab="history">History</button>
    </div>

    <div class="tab-panel is-active" data-panel="create">
        <form class="requisition-form" method="post" action="<?= base_url('staff/dashboard/inventory/requisitions'); ?>" id="req-form">
            <div class="requisition-items" id="requisition-items">
                <div class="requisition-item-row" data-item-index="0">
                    <div class="form-grid-item">
                        <label>
                            <span>Item</span>
                            <div class="item-select-wrapper">
                                <input type="text" class="item-search" placeholder="Search items..." autocomplete="off">
                                <select name="inventory_item_id[]" class="item-select" onchange="handleItemSelect(this)" required>
                                    <option value="">Select item</option>
                                    <?php foreach ($inventoryItems as $item): ?>
                                        <option value="<?= (int)$item['id']; ?>" data-search="<?= htmlspecialchars(strtolower($item['name'] . ' ' . $item['sku'])); ?>">
                                            <?= htmlspecialchars($item['name']); ?> (<?= htmlspecialchars($item['sku']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom" data-search="custom enter">+ Enter Custom Item</option>
                                </select>
                            </div>
                            <input type="text" name="custom_item_name[]" class="custom-item-input" placeholder="Enter item name" style="display: none; margin-top: 0.5rem;">
                            <input type="hidden" name="is_custom_item[]" class="is-custom-flag" value="0">
                        </label>
                        <label>
                            <span>Quantity</span>
                            <input type="number" name="quantity[]" step="0.01" min="0.01" placeholder="e.g. 2" required>
                        </label>
                        <div class="item-actions">
                            <button type="button" class="btn btn-outline btn-small" onclick="addItemRow()">+ Add Item</button>
                            <button type="button" class="btn btn-ghost btn-small remove-item" onclick="removeItemRow(this)" style="display: none;">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-fields">
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
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Submit Requisition</button>
            </div>
        </form>
    </div>

<section class="card tab-panel" data-panel="queue">
    <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <h3 style="margin: 0;">Requisition Queue</h3>
        <div style="display: flex; gap: 0.5rem; margin-left: auto;">
            <?php if ($canViewAll ?? false): ?>
                <select id="filter-view" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;" onchange="updateFilterView(this.value)">
                    <option value="all" <?= ($filters['filter'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Requests</option>
                    <option value="department" <?= ($filters['filter'] ?? '') === 'department' ? 'selected' : ''; ?>>My Department</option>
                    <option value="mine" <?= ($filters['filter'] ?? '') === 'mine' ? 'selected' : ''; ?>>My Requests</option>
                </select>
            <?php else: ?>
                <select id="filter-view" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;" onchange="updateFilterView(this.value)">
                    <option value="department" <?= ($filters['filter'] ?? 'department') === 'department' ? 'selected' : ''; ?>>My Department</option>
                    <option value="mine" <?= ($filters['filter'] ?? '') === 'mine' ? 'selected' : ''; ?>>My Requests</option>
                </select>
            <?php endif; ?>
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
                $canVerifyOps = \App\Support\DepartmentHelper::canVerifyOperations($userRole);
                $canApproveFinance = \App\Support\DepartmentHelper::canApproveFinance($userRole);
                $canAssignSuppliers = \App\Support\DepartmentHelper::canAssignSuppliers($userRole);
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
                                // Ops Verification Actions - only show to roles that can verify
                                if (!$opsVerified && $canVerifyOps && $status === 'pending'):
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
                                // Finance Approval Actions - only show to roles that can approve finance
                                elseif ($opsVerified && !$financeApproved && $canApproveFinance && $status === 'pending'):
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
                                // Supplier Assignment & PO Creation - only show to roles that can assign suppliers
                                elseif ($financeApproved && $status === 'approved' && $canAssignSuppliers):
                                ?>
                                    <?php if (empty($req['supplier_id'])): ?>
                                        <?php
                                        // Get suggested suppliers for the first item
                                        $firstItemId = $req['items'][0]['inventory_item_id'] ?? null;
                                        $suggestedSuppliers = [];
                                        if ($firstItemId) {
                                            $suggestedSuppliers = $supplierRepo->getSuggestedSuppliers((int)$firstItemId, 5);
                                        }
                                        // Filter suppliers by category (product suppliers only for inventory requisitions)
                                        $productSuppliers = array_filter($allSuppliers, function($s) {
                                            return in_array($s['category'] ?? 'product_supplier', ['product_supplier', 'both']) 
                                                && in_array($s['status'] ?? 'active', ['active']);
                                        });
                                        ?>
                                        <form method="post" action="<?= base_url('staff/dashboard/inventory/requisitions/assign-supplier'); ?>" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <input type="hidden" name="id" value="<?= (int)$req['id']; ?>">
                                            <select name="supplier_id" required style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem;" id="supplier-select-<?= $req['id']; ?>" onchange="showSupplierInfo(this.value, <?= $req['id']; ?>)">
                                                <option value="">Select Supplier</option>
                                                <?php if (!empty($suggestedSuppliers)): ?>
                                                    <optgroup label="Recommended Suppliers">
                                                        <?php foreach ($suggestedSuppliers as $supplier): ?>
                                                            <option value="<?= (int)$supplier['id']; ?>" data-reliability="<?= htmlspecialchars($supplier['reliability_score'] ?? '0'); ?>" data-price="<?= htmlspecialchars($supplier['unit_price'] ?? ''); ?>" data-delivery="<?= htmlspecialchars($supplier['estimated_delivery_days'] ?? ''); ?>">
                                                                <?= htmlspecialchars($supplier['name']); ?>
                                                                <?php if (!empty($supplier['unit_price'])): ?>
                                                                    - KES <?= number_format((float)$supplier['unit_price'], 2); ?>
                                                                <?php endif; ?>
                                                                <?php if (!empty($supplier['reliability_score'])): ?>
                                                                    (<?= number_format((float)$supplier['reliability_score'], 0); ?>% reliable)
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endif; ?>
                                                <optgroup label="All Suppliers">
                                                    <?php foreach ($productSuppliers as $supplier): ?>
                                                        <?php if (empty($suggestedSuppliers) || !in_array((int)$supplier['id'], array_column($suggestedSuppliers, 'id'))): ?>
                                                            <option value="<?= (int)$supplier['id']; ?>" <?= ($req['items'][0]['preferred_supplier_id'] ?? null) == $supplier['id'] ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($supplier['name']); ?>
                                                                <?= ($req['items'][0]['preferred_supplier_id'] ?? null) == $supplier['id'] ? '(Preferred)' : ''; ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            </select>
                                            <div id="supplier-info-<?= $req['id']; ?>" style="display: none; padding: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.875rem; margin-top: 0.5rem;">
                                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                                                    <div><strong>Reliability:</strong> <span id="info-reliability-<?= $req['id']; ?>">—</span></div>
                                                    <div><strong>Est. Price:</strong> <span id="info-price-<?= $req['id']; ?>">—</span></div>
                                                    <div><strong>Est. Delivery:</strong> <span id="info-delivery-<?= $req['id']; ?>">—</span></div>
                                                    <div><strong>Status:</strong> <span id="info-status-<?= $req['id']; ?>">—</span></div>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary btn-small" type="submit">Assign Supplier</button>
                                        </form>
                                        <script>
                                        function showSupplierInfo(supplierId, reqId) {
                                            const select = document.getElementById('supplier-select-' + reqId);
                                            const option = select.options[select.selectedIndex];
                                            const infoDiv = document.getElementById('supplier-info-' + reqId);
                                            
                                            if (supplierId && option) {
                                                const reliability = option.getAttribute('data-reliability') || '—';
                                                const price = option.getAttribute('data-price') || '—';
                                                const delivery = option.getAttribute('data-delivery') || '—';
                                                
                                                document.getElementById('info-reliability-' + reqId).textContent = reliability !== '—' ? reliability + '%' : '—';
                                                document.getElementById('info-price-' + reqId).textContent = price !== '—' ? 'KES ' + parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '—';
                                                document.getElementById('info-delivery-' + reqId).textContent = delivery !== '—' ? delivery + ' days' : '—';
                                                
                                                // Get supplier status from allSuppliers array (would need to pass this data)
                                                infoDiv.style.display = 'block';
                                            } else {
                                                infoDiv.style.display = 'none';
                                            }
                                        }
                                        </script>
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

<style>
    .requisition-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .requisition-items {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .requisition-item-row {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
    }

    .form-grid-item {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .form-grid-item label {
        display: flex;
        flex-direction: column;
        font-weight: 600;
        color: #475569;
    }

    .form-grid-item label span {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-grid-item select,
    .form-grid-item input {
        padding: 0.5rem;
        border: 1px solid #cbd5f5;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .item-select-wrapper {
        position: relative;
    }

    .item-search {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.5rem;
        border: 1px solid #cbd5f5;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .item-select {
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
    }

    .item-select option {
        padding: 0.5rem;
    }

    .custom-item-input {
        width: 100%;
    }

    .custom-item-input:required {
        border-color: #ef4444;
    }

    .item-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .form-fields {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .form-fields label {
        display: flex;
        flex-direction: column;
        font-weight: 600;
        color: #475569;
    }

    .form-fields label span {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-fields input,
    .form-fields select {
        padding: 0.5rem;
        border: 1px solid #cbd5f5;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .form-grid-item {
            grid-template-columns: 1fr;
        }

        .item-actions {
            flex-direction: column;
            width: 100%;
        }

        .item-actions button {
            width: 100%;
        }
    }
</style>

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

    // Initialize item search for all existing rows
    document.querySelectorAll('.item-search').forEach(searchInput => {
        initItemSearch(searchInput);
    });

    updateRemoveButtons();
});

function initItemSearch(searchInput) {
    const select = searchInput.nextElementSibling;
    if (!select || !select.classList.contains('item-select')) return;

    const allOptions = Array.from(select.options);
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        // Filter options
        allOptions.forEach(option => {
            if (option.value === '') {
                option.style.display = ''; // Always show "Select item"
                return;
            }
            
            const searchText = option.dataset.search || option.textContent.toLowerCase();
            if (searchText.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // If search is empty, reset to first option
        if (!searchTerm && select.value === '') {
            select.selectedIndex = 0;
        }
    });

    // Clear search when item is selected
    select.addEventListener('change', function() {
        if (this.value && this.value !== 'custom') {
            searchInput.value = '';
            // Reset all options to visible
            allOptions.forEach(option => {
                option.style.display = '';
            });
        }
    });
}

let itemIndex = 1;

function addItemRow() {
    const itemsContainer = document.getElementById('requisition-items');
    const newRow = document.createElement('div');
    newRow.className = 'requisition-item-row';
    newRow.setAttribute('data-item-index', itemIndex);
    
    const inventoryItems = <?= json_encode(array_map(function($item) {
        return ['id' => (int)$item['id'], 'name' => $item['name'], 'sku' => $item['sku']];
    }, $inventoryItems)); ?>;
    
    let optionsHtml = '<option value="">Select item</option>';
    inventoryItems.forEach(item => {
        const searchText = `${item.name.toLowerCase()} ${item.sku.toLowerCase()}`;
        optionsHtml += `<option value="${item.id}" data-search="${escapeHtml(searchText)}">${escapeHtml(item.name)} (${escapeHtml(item.sku)})</option>`;
    });
    optionsHtml += '<option value="custom" data-search="custom enter">+ Enter Custom Item</option>';
    
    newRow.innerHTML = `
        <div class="form-grid-item">
            <label>
                <span>Item</span>
                <div class="item-select-wrapper">
                    <input type="text" class="item-search" placeholder="Search items..." autocomplete="off">
                    <select name="inventory_item_id[]" class="item-select" onchange="handleItemSelect(this)" required>
                        ${optionsHtml}
                    </select>
                </div>
                <input type="text" name="custom_item_name[]" class="custom-item-input" placeholder="Enter item name" style="display: none; margin-top: 0.5rem;">
                <input type="hidden" name="is_custom_item[]" class="is-custom-flag" value="0">
            </label>
            <label>
                <span>Quantity</span>
                <input type="number" name="quantity[]" step="0.01" min="0.01" placeholder="e.g. 2" required>
            </label>
            <div class="item-actions">
                <button type="button" class="btn btn-outline btn-small" onclick="addItemRow()">+ Add Item</button>
                <button type="button" class="btn btn-ghost btn-small remove-item" onclick="removeItemRow(this)">Remove</button>
            </div>
        </div>
    `;
    
    itemsContainer.appendChild(newRow);
    itemIndex++;
    updateRemoveButtons();
    
    // Initialize search for the new row
    const newSearchInput = newRow.querySelector('.item-search');
    if (newSearchInput) {
        initItemSearch(newSearchInput);
    }
}

function removeItemRow(button) {
    const row = button.closest('.requisition-item-row');
    if (row) {
        row.remove();
        updateRemoveButtons();
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.requisition-item-row');
    const removeButtons = document.querySelectorAll('.remove-item');
    
    if (rows.length > 1) {
        removeButtons.forEach(btn => btn.style.display = 'block');
    } else {
        removeButtons.forEach(btn => btn.style.display = 'none');
    }
}

function handleItemSelect(selectElement) {
    const row = selectElement.closest('.requisition-item-row');
    const customInput = row.querySelector('.custom-item-input');
    const isCustomFlag = row.querySelector('.is-custom-flag');
    const select = row.querySelector('.item-select');
    
    if (selectElement.value === 'custom') {
        // Show custom input and make it required
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.setAttribute('required', 'required');
        isCustomFlag.value = '1';
        select.removeAttribute('required');
        select.name = 'inventory_item_id_temp[]'; // Change name so it doesn't submit
        // Clear select value
        select.value = '';
    } else {
        // Hide custom input
        customInput.style.display = 'none';
        customInput.removeAttribute('required');
        customInput.value = '';
        isCustomFlag.value = '0';
        select.required = true;
        select.setAttribute('required', 'required');
        select.name = 'inventory_item_id[]'; // Restore name
    }
}

// Form validation before submit
document.getElementById('req-form')?.addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.requisition-item-row');
    let isValid = true;
    
    rows.forEach(row => {
        const select = row.querySelector('.item-select');
        const customInput = row.querySelector('.custom-item-input');
        const isCustom = row.querySelector('.is-custom-flag').value === '1';
        
        if (isCustom) {
            if (!customInput.value.trim()) {
                isValid = false;
                customInput.style.borderColor = '#ef4444';
            } else {
                customInput.style.borderColor = '';
            }
        } else {
            if (!select.value || select.value === '') {
                isValid = false;
                select.style.borderColor = '#ef4444';
            } else {
                select.style.borderColor = '';
            }
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateFilterView(filter) {
    const url = new URL(window.location.href);
    url.searchParams.set('filter', filter);
    window.location.href = url.toString();
}

function applyFilters() {
    const type = document.getElementById('filter-type')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const view = document.getElementById('filter-view')?.value || 'department';
    
    const url = new URL(window.location.href);
    if (type) url.searchParams.set('type', type);
    else url.searchParams.delete('type');
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    url.searchParams.set('filter', view);
    
    window.location.href = url.toString();
}
</script>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

