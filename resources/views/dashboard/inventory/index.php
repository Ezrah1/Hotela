<?php
$pageTitle = 'Inventory | Hotela';
ob_start();
?>
<section class="card">
    <header class="inventory-header">
        <div>
            <h2>Inventory</h2>
            <p class="inventory-subtitle">Manage stock levels and track inventory</p>
        </div>
        <div class="header-actions">
            <?php
            $userRole = (\App\Support\Auth::user()['role_key'] ?? (\App\Support\Auth::user()['role'] ?? ''));
            if (in_array($userRole, ['admin', 'operation_manager'])):
            ?>
                <a href="<?= base_url('staff/dashboard/inventory/item/create'); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Item
                </a>
            <?php endif; ?>
            <?php if ($valuation !== null): ?>
                <div class="valuation-badge">
                    <span class="valuation-label">Total Valuation</span>
                    <span class="valuation-value">KES <?= number_format((float)$valuation, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">
            <?php
            if ($_GET['success'] === 'autoimport') {
                $created = (int)($_GET['created'] ?? 0);
                $mapped = (int)($_GET['mapped'] ?? 0);
                echo "Auto-import completed: {$created} items created, {$mapped} items mapped.";
            } else {
                echo 'Operation completed successfully.';
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($unmappedCount)): ?>
        <div class="info-banner">
            <div class="info-banner-content">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div>
                    <strong><?= (int)$unmappedCount; ?> POS items</strong> are not mapped to inventory components.
                    <span class="info-banner-hint">Sales won't deduct stock for these items.</span>
                </div>
            </div>
            <?php
            $userRole = (\App\Support\Auth::user()['role_key'] ?? (\App\Support\Auth::user()['role'] ?? ''));
            if (in_array($userRole, ['admin','operation_manager'])):
            ?>
                <form method="post" action="<?= base_url('staff/dashboard/inventory/auto-import'); ?>" style="margin-top: 0.75rem;">
                    <button class="btn btn-primary btn-small" type="submit">Auto-import & Map</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="inventory-actions">
        <?php if (!empty($canRequisitions)): ?>
            <a href="<?= base_url('staff/dashboard/inventory/requisitions'); ?>" class="action-card">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <div>
                    <h4>Requisitions</h4>
                    <p>Create and track purchase orders</p>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
        <?php if (!empty($canAdjust)): ?>
            <div class="action-card">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <div>
                    <h4>Quick Adjust</h4>
                    <p>Adjust stock levels</p>
                </div>
                <button type="button" class="btn-toggle" onclick="document.getElementById('adjust-form').classList.toggle('is-open')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        <?php if (!empty($canApprove)): ?>
            <a href="<?= base_url('staff/dashboard/inventory/requisitions'); ?>" class="action-card">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <div>
                    <h4>Approvals</h4>
                    <p>Review pending orders</p>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($canAdjust)): ?>
        <div class="adjust-form" id="adjust-form">
            <form method="post" action="#" onsubmit="alert('Coming soon');return false;">
                <div class="form-grid">
                    <label>
                        <span>Item</span>
                        <select name="inventory_item_id">
                            <?php foreach ($inventoryItems as $it): ?>
                                <option value="<?= (int)$it['id']; ?>"><?= htmlspecialchars($it['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Location</span>
                        <select name="location_id">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= (int)$loc['id']; ?>"><?= htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Quantity Adjustment</span>
                        <input type="number" step="0.01" name="quantity" placeholder="e.g. -2 or +5">
                    </label>
                </div>
                <button class="btn btn-primary" type="submit">Apply Adjustment</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="inventory-filters">
        <form method="get" action="<?= base_url('staff/dashboard/inventory'); ?>" class="filter-form">
            <div class="filter-inputs">
                <label>
                    <span>Category</span>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="category" class="filter-select" style="flex: 1;">
                            <option value="">All Categories</option>
                            <?php foreach (($categories ?? []) as $cat): ?>
                                <option value="<?= htmlspecialchars($cat); ?>" <?= ($activeCategory ?? '') === $cat ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($canManageCategories ?? false)): ?>
                            <button type="button" class="btn btn-outline btn-small" onclick="document.getElementById('add-category-form').classList.toggle('is-open')" title="Add Category" style="flex-shrink: 0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                </label>
                <?php if (!empty($canManageCategories ?? false)): ?>
                    <div class="add-category-form" id="add-category-form" style="display: none; margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <form method="post" action="<?= base_url('staff/dashboard/inventory/category/create'); ?>" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                            <label style="flex: 1;">
                                <span style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #64748b; font-weight: 500;">New Category Name</span>
                                <input type="text" name="category_name" required placeholder="e.g., Beverages, Cleaning Supplies" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.375rem;">
                            </label>
                            <button type="submit" class="btn btn-primary btn-small">Add</button>
                            <button type="button" class="btn btn-outline btn-small" onclick="document.getElementById('add-category-form').classList.remove('is-open')">Cancel</button>
                        </form>
                        <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.75rem;">
                            Note: Category will appear in the dropdown once created. You can assign items to this category when creating or editing inventory items.
                        </small>
                    </div>
                <?php endif; ?>
                <label>
                    <span>Search</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search ?? ''); ?>" placeholder="Search by name or SKU..." class="filter-input">
                </label>
                <button class="btn btn-outline" type="submit">Apply Filters</button>
                <?php if ($activeCategory || $search): ?>
                    <a href="<?= base_url('staff/dashboard/inventory'); ?>" class="btn btn-ghost">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($items ?? [])): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 7h-4M4 7h4m0 0v13a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V7M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            <h3>No items found</h3>
            <p>No inventory items match your current filters.</p>
        </div>
    <?php else: ?>
        <div class="inventory-table-wrapper">
            <table class="inventory-table">
                <thead>
                <tr>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Stock Level</th>
                    <th>Reorder Point</th>
                    <th>Unit</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($items ?? []) as $row): ?>
                    <?php
                    $stock = (float)($row['stock'] ?? 0);
                    $reorder = (float)($row['reorder_point'] ?? 0);
                    $low = $reorder > 0 && $stock <= $reorder;
                    ?>
                    <tr class="<?= $low ? 'row-low-stock' : ''; ?>">
                        <td>
                            <div class="item-name-cell">
                                <strong><?= htmlspecialchars($row['name'] ?? ''); ?></strong>
                                <?php
                                $userRole = (\App\Support\Auth::user()['role_key'] ?? (\App\Support\Auth::user()['role'] ?? ''));
                                if (in_array($userRole, ['admin', 'operation_manager'])):
                                ?>
                                    <div class="item-actions">
                                        <a href="<?= base_url('staff/dashboard/inventory/item/edit?id=' . (int)$row['id']); ?>" class="action-link" title="Edit">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <form method="post" action="<?= base_url('staff/dashboard/inventory/item/delete'); ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                            <button type="submit" class="action-link danger" title="Delete">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="sku-badge"><?= htmlspecialchars($row['sku'] ?? '—'); ?></span>
                        </td>
                        <td>
                            <span class="category-tag"><?= htmlspecialchars($row['category'] ?? '—'); ?></span>
                        </td>
                        <td>
                            <div class="stock-cell">
                                <div class="stock-main">
                                    <span class="stock-value <?= $low ? 'stock-low' : 'stock-ok'; ?>">
                                        <?= number_format($stock, 2); ?> <?= htmlspecialchars($row['unit'] ?? ''); ?>
                                    </span>
                                    <?php if ($low): ?>
                                        <span class="low-stock-indicator" title="Low stock - below reorder point">⚠</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($row['stock_by_location'] ?? [])): ?>
                                    <div class="stock-locations">
                                        <?php foreach ($row['stock_by_location'] as $loc): ?>
                                            <span class="location-stock" title="Available at <?= htmlspecialchars($loc['location_name'] ?? ''); ?>">
                                                <?= htmlspecialchars($loc['location_name'] ?? ''); ?>: 
                                                <strong><?= number_format((float)$loc['quantity'], 2); ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($stock > 0): ?>
                                    <div class="stock-locations">
                                        <span class="location-stock">Total: <?= number_format($stock, 2); ?> <?= htmlspecialchars($row['unit'] ?? ''); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="stock-locations">
                                        <span class="location-stock empty">No stock available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= number_format($reorder, 2); ?></td>
                        <td><?= htmlspecialchars($row['unit'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.inventory-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.inventory-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.valuation-badge {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    padding: 1rem 1.5rem;
    background: var(--accent-soft);
    border-radius: 0.75rem;
    border: 1px solid rgba(138, 106, 63, 0.2);
}

.valuation-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.valuation-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.info-banner {
    padding: 1rem 1.25rem;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.info-banner-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.info-banner-content svg {
    flex-shrink: 0;
    color: #f59e0b;
    margin-top: 0.125rem;
}

.info-banner-content strong {
    color: var(--dark);
}

.info-banner-hint {
    display: block;
    font-size: 0.875rem;
    color: #92400e;
    margin-top: 0.25rem;
}

.inventory-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.action-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.action-card svg:first-child {
    flex-shrink: 0;
    color: var(--primary);
}

.action-card h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
}

.action-card p {
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
}

.action-card svg:last-child {
    margin-left: auto;
    color: #cbd5e1;
    transition: transform 0.2s ease;
}

.action-card:hover svg:last-child {
    color: var(--primary);
    transform: translateX(4px);
}

.btn-toggle {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #cbd5e1;
    transition: all 0.2s ease;
}

.btn-toggle:hover {
    color: var(--primary);
}

.btn-toggle svg {
    transition: transform 0.2s ease;
}

.btn-toggle.active svg {
    transform: rotate(180deg);
}

.adjust-form {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    margin-bottom: 1.5rem;
}

.adjust-form.is-open {
    max-height: 500px;
    padding: 1.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.inventory-filters {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: flex-end;
}

.filter-select,
.filter-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.filter-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
}

.btn-ghost {
    padding: 0.625rem 1rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-ghost:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(138, 106, 63, 0.05);
}

.inventory-table-wrapper {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.inventory-table thead {
    background: #f8fafc;
}

.inventory-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}

.inventory-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.95rem;
    color: var(--dark);
}

.inventory-table tbody tr:last-child td {
    border-bottom: none;
}

.inventory-table tbody tr:hover {
    background: #f8fafc;
}

.inventory-table tbody tr.row-low-stock {
    background: #fef2f2;
}

.inventory-table tbody tr.row-low-stock:hover {
    background: #fee2e2;
}

.item-name-cell {
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.item-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.item-name-cell:hover .item-actions {
    opacity: 1;
}

.action-link {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem;
    color: #64748b;
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    transition: color 0.2s;
}

.action-link:hover {
    color: var(--primary);
}

.action-link.danger:hover {
    color: #ef4444;
}

.sku-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: #f1f5f9;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-family: 'Courier New', monospace;
    color: #475569;
}

.add-category-form.is-open {
    display: block !important;
}

.add-category-form {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.category-tag {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: rgba(138, 106, 63, 0.1);
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: var(--primary);
    font-weight: 500;
}

.stock-cell {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stock-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stock-value {
    font-weight: 600;
    font-size: 1rem;
}

.stock-ok {
    color: #22c55e;
}

.stock-low {
    color: #ef4444;
}

.low-stock-indicator {
    font-size: 1.125rem;
    color: #ef4444;
}

.stock-locations {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.location-stock {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f1f5f9;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.location-stock strong {
    color: var(--primary);
    font-weight: 600;
}

.location-stock.empty {
    color: #94a3b8;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: #cbd5e1;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

@media (max-width: 768px) {
    .inventory-header {
        flex-direction: column;
        gap: 1rem;
    }

    .valuation-badge {
        align-items: flex-start;
        width: 100%;
    }

    .inventory-actions {
        grid-template-columns: 1fr;
    }

    .filter-inputs {
        grid-template-columns: 1fr;
    }

    .inventory-table-wrapper {
        overflow-x: scroll;
    }
}
</style>

<script>
document.querySelector('.btn-toggle')?.addEventListener('click', function() {
    this.classList.toggle('active');
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
