<?php
$pageTitle = 'License Packages';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <h2>License Packages</h2>
        <p class="page-subtitle">Manage license packages and pricing</p>
    </div>
    <div>
        <button onclick="document.getElementById('addPackageModal').style.display='flex'" class="btn btn-primary">
            + Add New Package
        </button>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 2rem;">
        <?= htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;">
        <?php
        $messages = [
            'created' => 'Package created successfully.',
            'updated' => 'Package updated successfully.',
            'deleted' => 'Package deleted successfully.',
        ];
        echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully.');
        ?>
    </div>
<?php endif; ?>

<?php 
// Determine which package is "popular" (middle price point)
$activePackages = array_filter($packages, fn($p) => $p['is_active'] == 1);
if (count($activePackages) > 1) {
    $prices = array_column($activePackages, 'price');
    sort($prices);
    $midPrice = $prices[floor(count($prices) / 2)];
}
?>

<div class="sysadmin-card">
    <div class="card-body">
        <?php if (empty($packages)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; opacity: 0.3;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                <p>No packages found. Create your first package to get started.</p>
            </div>
        <?php else: ?>
            <div class="packages-grid">
                <?php foreach ($packages as $index => $package): ?>
                    <?php 
                    $isPopular = isset($midPrice) && abs($package['price'] - $midPrice) < 50 && $package['is_active'] == 1;
                    $isMonthly = $package['duration_months'] == 1;
                    ?>
                    <div class="package-card <?= !$package['is_active'] ? 'inactive' : ''; ?> <?= $isPopular ? 'popular' : ''; ?>">
                        <?php if ($isPopular): ?>
                            <div class="popular-badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                Most Popular
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-header">
                            <div>
                                <h3><?= htmlspecialchars($package['name']); ?></h3>
                                <?php if ($isMonthly): ?>
                                    <span class="package-badge monthly">Monthly</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$package['is_active']): ?>
                                <span class="badge badge-warning">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="package-price">
                            <div class="price-main">
                                <span class="currency"><?= htmlspecialchars($package['currency']); ?></span>
                                <span class="amount"><?= number_format($package['price'], 2); ?></span>
                            </div>
                            <div class="price-duration">
                                <span>/ <?= $package['duration_months']; ?> <?= $package['duration_months'] == 1 ? 'month' : 'months'; ?></span>
                                <?php if ($package['duration_months'] > 1): ?>
                                    <span class="price-per-month">≈ <?= htmlspecialchars($package['currency']); ?><?= number_format($package['price'] / $package['duration_months'], 2); ?>/month</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($package['description']): ?>
                            <p class="package-description"><?= htmlspecialchars($package['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($package['features']): ?>
                            <?php $features = json_decode($package['features'], true); ?>
                            <?php if (is_array($features) && !empty($features)): ?>
                                <div class="package-features-wrapper">
                                    <ul class="package-features">
                                        <?php foreach ($features as $feature): ?>
                                            <li>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                <?= htmlspecialchars($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="package-actions">
                            <button onclick="editPackage(<?= htmlspecialchars(json_encode($package)); ?>)" class="btn btn-sm btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit
                            </button>
                            <form method="POST" action="<?= base_url('sysadmin/packages'); ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $package['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Package Modal -->
<div id="addPackageModal" class="modal" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Package</h3>
            <button class="modal-close" onclick="document.getElementById('addPackageModal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('sysadmin/packages'); ?>" id="packageForm">
            <input type="hidden" name="action" value="create" id="formAction">
            <input type="hidden" name="id" id="packageId">
            <div class="form-group">
                <label for="name">Package Name *</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Professional Plan">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Brief description of the package..." style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-family: inherit; resize: vertical;"></textarea>
            </div>
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-family: inherit;">
                        <option value="USD">USD ($)</option>
                        <option value="KES">KES (KSh)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>
            </div>
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="duration_months">Duration (Months) *</label>
                    <input type="number" id="duration_months" name="duration_months" min="1" value="12" required>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" placeholder="0">
                </div>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked style="width: auto;">
                    <span>Active (visible to customers)</span>
                </label>
            </div>
            <div class="form-group">
                <label for="features">Features (one per line) *</label>
                <textarea id="features" name="features" rows="6" placeholder="Up to 20 rooms&#10;Basic booking management&#10;Guest management&#10;Email support" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-family: inherit; resize: vertical;"></textarea>
                <small style="color: #64748b; font-size: 0.875rem; display: block; margin-top: 0.5rem;">Enter one feature per line. These will be displayed as a bulleted list.</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addPackageModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Package</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    animation: fadeIn 0.2s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.modal-close {
    background: #f1f5f9;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s;
    background: white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #f1f5f9;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 0.9375rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}
</style>

<style>
.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
}

.package-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 1rem;
    padding: 2rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.package-card:hover {
    border-color: #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
}

.package-card.popular {
    border-color: #667eea;
    border-width: 3px;
    background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
}

.package-card.popular:hover {
    box-shadow: 0 15px 50px rgba(102, 126, 234, 0.25);
    transform: translateY(-6px);
}

.package-card.inactive {
    opacity: 0.5;
    background: #f8fafc;
}

.package-card.inactive:hover {
    transform: none;
}

.popular-badge {
    position: absolute;
    top: -12px;
    right: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    z-index: 1;
}

.popular-badge svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.package-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.package-header > div {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.package-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
}

.package-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.package-badge.monthly {
    background: #eff6ff;
    color: #2563eb;
}

.package-price {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f1f5f9;
}

.price-main {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.package-price .currency {
    font-size: 1.25rem;
    color: #64748b;
    font-weight: 500;
}

.package-price .amount {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.price-duration {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.price-duration > span:first-child {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.price-per-month {
    font-size: 0.75rem;
    color: #94a3b8;
    font-style: italic;
}

.package-description {
    color: #64748b;
    margin-bottom: 1.5rem;
    font-size: 0.9375rem;
    line-height: 1.6;
}

.package-features-wrapper {
    flex: 1;
    margin-bottom: 1.5rem;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.package-features li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: #475569;
    font-size: 0.875rem;
    line-height: 1.5;
}

.package-features li svg {
    flex-shrink: 0;
    margin-top: 0.125rem;
    color: #10b981;
    stroke-width: 3;
}

.package-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.btn-sm {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-sm svg {
    width: 16px;
    height: 16px;
}

.btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.btn-danger {
    background: #fee2e2;
    color: #dc2626;
    border: 2px solid #fecaca;
}

.btn-danger:hover {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-state svg {
    display: block;
    margin: 0 auto 1rem;
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

@media (max-width: 768px) {
    .packages-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .package-card {
        padding: 1.5rem;
    }
    
    .package-price .amount {
        font-size: 2.5rem;
    }
}
</style>

<script>
function editPackage(package) {
    document.getElementById('modalTitle').textContent = 'Edit Package';
    document.getElementById('formAction').value = 'update';
    document.getElementById('packageId').value = package.id;
    document.getElementById('name').value = package.name;
    document.getElementById('description').value = package.description || '';
    document.getElementById('price').value = package.price;
    document.getElementById('currency').value = package.currency || 'USD';
    document.getElementById('duration_months').value = package.duration_months || 12;
    document.getElementById('sort_order').value = package.sort_order || 0;
    document.getElementById('is_active').checked = package.is_active == 1;
    
    let features = '';
    if (package.features) {
        try {
            const featuresArray = JSON.parse(package.features);
            if (Array.isArray(featuresArray)) {
                features = featuresArray.join('\n');
            }
        } catch (e) {
            // Invalid JSON, leave empty
        }
    }
    document.getElementById('features').value = features;
    
    document.getElementById('addPackageModal').style.display = 'flex';
}

// Reset form when modal is closed
document.getElementById('addPackageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        resetForm();
    }
});

document.querySelector('.modal-close').addEventListener('click', resetForm);

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Add New Package';
    document.getElementById('formAction').value = 'create';
    document.getElementById('packageId').value = '';
    document.getElementById('packageForm').reset();
}
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

