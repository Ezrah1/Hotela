<?php
$pageTitle = 'Suppliers Management | Hotela';
$suppliers = $suppliers ?? [];
$search = $search ?? '';

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

ob_start();
?>
<section class="card">
    <header class="suppliers-header">
        <div>
            <h2>Suppliers Management</h2>
            <p class="suppliers-subtitle">Manage your supplier accounts and vendor relationships</p>
        </div>
        <a href="<?= base_url('staff/dashboard/suppliers/create'); ?>" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Supplier
        </a>
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

    <form method="get" action="<?= base_url('staff/dashboard/suppliers'); ?>" class="suppliers-filters">
        <div class="filters-row">
            <div class="search-wrapper">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search); ?>" 
                    placeholder="Search suppliers..."
                    class="search-input"
                >
                <?php if ($search): ?>
                    <a href="<?= base_url('staff/dashboard/suppliers?' . http_build_query(array_filter(['category' => $category, 'status' => $status, 'group' => $group]))); ?>" class="search-clear">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <option value="product_supplier" <?= $category === 'product_supplier' ? 'selected' : ''; ?>>Product Suppliers</option>
                <option value="service_provider" <?= $category === 'service_provider' ? 'selected' : ''; ?>>Service Providers</option>
                <option value="both" <?= $category === 'both' ? 'selected' : ''; ?>>Both</option>
            </select>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                <option value="blacklisted" <?= $status === 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <select name="group" class="filter-select">
                <option value="">All Groups</option>
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $groupName): ?>
                        <option value="<?= htmlspecialchars($groupName); ?>" <?= $group === $groupName ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($groupName); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $category || $status || $group): ?>
                <a href="<?= base_url('staff/dashboard/suppliers'); ?>" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($suppliers)): ?>
        <div class="empty-state">
            <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <h3>No Suppliers Found</h3>
            <p><?= $search ? 'No suppliers match your search criteria.' : 'Get started by adding your first supplier.'; ?></p>
            <?php if (!$search): ?>
                <a href="<?= base_url('staff/dashboard/suppliers/create'); ?>" class="btn btn-primary">Add Supplier</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="suppliers-grid">
            <?php foreach ($suppliers as $supplier): ?>
                <div class="supplier-card">
                    <div class="supplier-card-header">
                        <div class="supplier-name-section">
                            <h3 class="supplier-name"><?= htmlspecialchars($supplier['name']); ?></h3>
                            <div class="supplier-badges">
                                <span class="supplier-status status-<?= $supplier['status']; ?>">
                                    <?= ucfirst($supplier['status']); ?>
                                </span>
                                <?php if (!empty($supplier['category'])): ?>
                                    <span class="supplier-category category-<?= $supplier['category']; ?>">
                                        <?php
                                        $categoryLabels = [
                                            'product_supplier' => 'Products',
                                            'service_provider' => 'Services',
                                            'both' => 'Both'
                                        ];
                                        echo $categoryLabels[$supplier['category']] ?? ucfirst($supplier['category']);
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($supplier['supplier_group'])): ?>
                                    <span class="supplier-group">
                                        <?= htmlspecialchars($supplier['supplier_group']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="supplier-actions">
                            <a href="<?= base_url('staff/dashboard/suppliers/show?id=' . $supplier['id']); ?>" class="btn-icon" title="View Details">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="<?= base_url('staff/dashboard/suppliers/edit?id=' . $supplier['id']); ?>" class="btn-icon" title="Edit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="supplier-info">
                        <?php if (!empty($supplier['contact_person'])): ?>
                            <div class="info-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><?= htmlspecialchars($supplier['contact_person']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($supplier['email'])): ?>
                            <div class="info-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span><?= htmlspecialchars($supplier['email']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($supplier['phone'])): ?>
                            <div class="info-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <span><?= htmlspecialchars($supplier['phone']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($supplier['city']) || !empty($supplier['country'])): ?>
                            <div class="info-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span>
                                    <?= htmlspecialchars(trim(($supplier['city'] ?? '') . ', ' . ($supplier['country'] ?? ''), ', ')); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="supplier-stats">
                        <div class="stat-item">
                            <span class="stat-label">Purchase Orders</span>
                            <span class="stat-value"><?= number_format($supplier['purchase_order_count'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Pending</span>
                            <span class="stat-value stat-warning"><?= number_format($supplier['pending_po_count'] ?? 0); ?></span>
                        </div>
                        <?php if (!empty($supplier['total_spent'])): ?>
                            <div class="stat-item">
                                <span class="stat-label">Total Spent</span>
                                <span class="stat-value stat-primary">KES <?= number_format($supplier['total_spent'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.suppliers-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.suppliers-header h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.suppliers-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.suppliers-filters {
    margin-bottom: 2rem;
}

.filters-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-wrapper {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1rem;
    color: #94a3b8;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    font-family: inherit;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.search-clear {
    position: absolute;
    right: 0.75rem;
    color: #94a3b8;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}

.search-clear:hover {
    color: var(--primary);
}

.suppliers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.supplier-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.supplier-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.supplier-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.25rem;
}

.supplier-name-section {
    flex: 1;
}

.supplier-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
}

.supplier-status {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.supplier-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.supplier-status.status-active {
    background: #dcfce7;
    color: #16a34a;
}

.supplier-status.status-suspended {
    background: #fef3c7;
    color: #d97706;
}

.supplier-status.status-blacklisted {
    background: #fee2e2;
    color: #dc2626;
}

.supplier-status.status-inactive {
    background: #e2e8f0;
    color: #64748b;
}

.supplier-category {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #e0f2fe;
    color: #0369a1;
}

.supplier-group {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: #f1f5f9;
    color: #475569;
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: #fff;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(138, 106, 63, 0.1);
}

.supplier-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-icon:hover {
    color: var(--primary);
    background: rgba(138, 106, 63, 0.1);
    border-color: var(--primary);
}

.supplier-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
}

.info-item svg {
    color: #94a3b8;
    flex-shrink: 0;
}

.supplier-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
}

.stat-value.stat-primary {
    color: var(--primary);
}

.stat-value.stat-warning {
    color: #f59e0b;
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
    margin: 0 0 1.5rem 0;
    color: #64748b;
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
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .suppliers-header {
        flex-direction: column;
        gap: 1rem;
    }

    .filters-row {
        flex-direction: column;
    }

    .search-wrapper,
    .filter-select {
        width: 100%;
    }

    .suppliers-grid {
        grid-template-columns: 1fr;
    }

    .supplier-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

