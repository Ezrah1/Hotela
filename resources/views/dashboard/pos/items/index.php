<?php
$pageTitle = 'POS Items | Hotela';
ob_start();
?>
<section class="card">
    <header class="inventory-header">
        <div>
            <h2>POS Items</h2>
            <p class="inventory-subtitle">Manage POS items, recipes, and inventory classification</p>
        </div>
        <div class="header-actions">
            <a href="<?= base_url('staff/dashboard/pos/items/create'); ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add POS Item
            </a>
        </div>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Production Cost</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center muted">No POS items found. <a href="<?= base_url('staff/dashboard/pos/items/create'); ?>">Create one</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name']); ?></strong></td>
                            <td><?= htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($item['sku'] ?? '-'); ?></td>
                            <td>KES <?= number_format((float)$item['price'], 2); ?></td>
                            <td>
                                <?php if ((float)($item['production_cost'] ?? 0) > 0): ?>
                                    <span class="text-muted">KES <?= number_format((float)$item['production_cost'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['is_inventory_item'])): ?>
                                    <span class="badge badge-info">Inventory Item</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">POS Item</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url('staff/dashboard/pos/items/edit?id=' . $item['id']); ?>" class="btn btn-outline btn-small">Edit</a>
                                <form method="post" action="<?= base_url('staff/dashboard/pos/items/delete'); ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <input type="hidden" name="id" value="<?= $item['id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.inventory-subtitle {
    color: #64748b;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background: #f1f5f9;
    color: #475569;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

