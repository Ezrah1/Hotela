<?php
$pageTitle = 'Assign License Package';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <a href="<?= base_url('sysadmin/tenants/view?id=' . $tenant['id']); ?>" class="btn-link" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Tenant
        </a>
        <h2>Assign License Package</h2>
        <p class="page-subtitle">Select a license package for <?= htmlspecialchars($tenant['name']); ?></p>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 2rem;">
        <?php
        $errors = [
            'missing_data' => 'Please select a package.',
            'not_found' => 'Tenant or package not found.',
            'no_director' => 'No director user found for this tenant. Please create a director user first.',
        ];
        echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<div class="packages-selection-grid">
    <?php foreach ($packages as $package): ?>
        <?php 
        $isPopular = false;
        if (count($packages) > 1) {
            $prices = array_column($packages, 'price');
            sort($prices);
            $midPrice = $prices[floor(count($prices) / 2)];
            $isPopular = abs($package['price'] - $midPrice) < 50;
        }
        ?>
        <div class="package-select-card <?= $isPopular ? 'popular' : ''; ?>">
            <?php if ($isPopular): ?>
                <div class="popular-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    Recommended
                </div>
            <?php endif; ?>
            
            <div class="package-header">
                <h3><?= htmlspecialchars($package['name']); ?></h3>
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
                            <?php foreach (array_slice($features, 0, 5) as $feature): ?>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?= htmlspecialchars($feature); ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if (count($features) > 5): ?>
                                <li style="color: #64748b; font-style: italic;">
                                    +<?= count($features) - 5; ?> more features
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <form method="POST" action="<?= base_url('sysadmin/packages/assign'); ?>" style="margin-top: auto;">
                <input type="hidden" name="tenant_id" value="<?= $tenant['id']; ?>">
                <input type="hidden" name="package_id" value="<?= $package['id']; ?>">
                <button type="submit" class="btn btn-primary btn-full" onclick="return confirm('Assign this package to <?= htmlspecialchars($tenant['name']); ?>? A license will be generated automatically.')">
                    Assign Package
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<style>
.packages-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
}

.package-select-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 1rem;
    padding: 2rem;
    transition: all 0.3s;
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.package-select-card:hover {
    border-color: #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
}

.package-select-card.popular {
    border-color: #667eea;
    border-width: 3px;
    background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
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

.package-header h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
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

.btn-full {
    width: 100%;
    justify-content: center;
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

