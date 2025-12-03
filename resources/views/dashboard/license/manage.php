<?php
// This view is included via dashboard/base.php
// Variables available: $license, $packages
?>

<style>
.license-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.license-card h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.5rem;
}

.license-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.info-label {
    font-size: 0.875rem;
    color: #666;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
}

.package-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: #fff;
    transition: all 0.3s ease;
    position: relative;
}

.package-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.package-card.current {
    border-color: #10b981;
    background: #f0fdf4;
}

.package-card.current::before {
    content: "Current Package";
    position: absolute;
    top: -12px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.package-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.package-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.package-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
}

.package-duration {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

.package-description {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}

.package-features li {
    padding: 0.5rem 0;
    padding-left: 1.5rem;
    position: relative;
    color: #555;
}

.package-features li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.btn-upgrade {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
}

.btn-upgrade:hover:not(:disabled) {
    background: #5568d3;
}

.btn-upgrade:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-downgrade {
    background: #f59e0b;
    color: white;
}

.btn-downgrade:hover:not(:disabled) {
    background: #d97706;
}

.no-license {
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background: #f0fdf4;
    color: #166534;
}

.badge-warning {
    background: #fffbeb;
    color: #d97706;
}

.badge-danger {
    background: #fef2f2;
    color: #dc2626;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">License Management</h2>
            
            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error">
                    <strong>Error!</strong> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($license): ?>
                <div class="license-card">
                    <h3>Current License</h3>
                    <div class="license-info-grid">
                        <div class="info-item">
                            <div class="info-label">License Key</div>
                            <div class="info-value">
                                <code style="font-size: 0.875rem;"><?= htmlspecialchars($license['license_key']) ?></code>
                                <button onclick="copyToClipboard('<?= htmlspecialchars($license['license_key']) ?>')" 
                                        style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="badge badge-<?= $license['status'] === 'active' ? 'success' : ($license['status'] === 'expired' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($license['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($license['expires_at']): ?>
                            <div class="info-item">
                                <div class="info-label">Expires At</div>
                                <div class="info-value">
                                    <?= date('F j, Y', strtotime($license['expires_at'])) ?>
                                    <?php
                                    $expiresAt = new DateTime($license['expires_at']);
                                    $now = new DateTime();
                                    $daysLeft = $now->diff($expiresAt)->days;
                                    if ($expiresAt > $now) {
                                        echo '<span style="color: #666; font-size: 0.875rem;"> (' . $daysLeft . ' days remaining)</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($license['activated_at']): ?>
                            <div class="info-item">
                                <div class="info-label">Activated At</div>
                                <div class="info-value"><?= date('F j, Y', strtotime($license['activated_at'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($license['package_name']): ?>
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                            <h4 style="margin-bottom: 1rem;">Current Package</h4>
                            <div class="info-item">
                                <div class="info-label">Package Name</div>
                                <div class="info-value"><?= htmlspecialchars($license['package_name']) ?></div>
                            </div>
                            <?php if ($license['package_description']): ?>
                                <div class="info-item" style="margin-top: 0.5rem;">
                                    <div class="info-label">Description</div>
                                    <div class="info-value" style="font-weight: normal;"><?= htmlspecialchars($license['package_description']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($license['package_features']): ?>
                                <div style="margin-top: 1rem;">
                                    <div class="info-label">Features</div>
                                    <ul class="package-features">
                                        <?php
                                        $features = json_decode($license['package_features'], true);
                                        if (is_array($features)) {
                                            foreach ($features as $feature) {
                                                echo '<li>' . htmlspecialchars($feature) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: #fffbeb; border-radius: 4px; color: #d97706;">
                            <strong>No Package Assigned</strong> - This license was generated manually without a package.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-license">
                    <h3>No Active License Found</h3>
                    <p>You don't have an active license. Please contact the system administrator to get a license assigned.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($packages)): ?>
                <div style="margin-top: 2rem;">
                    <h3 class="mb-4">Available Packages</h3>
                    <p class="text-muted mb-4">Upgrade or downgrade your license to different packages based on your needs.</p>
                    
                    <?php
                    // Get current package price for comparison
                    $currentPackagePrice = null;
                    if ($license && isset($license['package_price'])) {
                        $currentPackagePrice = (float)$license['package_price'];
                    }
                    ?>
                    
                    <div class="row">
                        <?php foreach ($packages as $package): ?>
                            <?php
                            $packagePrice = (float)$package['price'];
                            $isCurrentPackage = ($license && $license['package_id'] == $package['id']);
                            $isHigher = $currentPackagePrice !== null && $packagePrice > $currentPackagePrice;
                            $isLower = $currentPackagePrice !== null && $packagePrice < $currentPackagePrice;
                            $isSame = $currentPackagePrice !== null && $packagePrice == $currentPackagePrice && !$isCurrentPackage;
                            
                            // Determine button text and action
                            if ($isCurrentPackage) {
                                $buttonText = 'Current Package';
                                $buttonDisabled = true;
                            } elseif ($isHigher) {
                                $buttonText = 'Upgrade to This Package';
                                $buttonDisabled = false;
                            } elseif ($isLower) {
                                $buttonText = 'Downgrade to This Package';
                                $buttonDisabled = false;
                            } else {
                                $buttonText = 'Switch to This Package';
                                $buttonDisabled = false;
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="package-card <?= $isCurrentPackage ? 'current' : '' ?>">
                                    <div class="package-header">
                                        <div>
                                            <h4 class="package-name"><?= htmlspecialchars($package['name']) ?></h4>
                                            <div class="package-duration"><?= $package['duration_months'] ?> months</div>
                                        </div>
                                        <div class="package-price">
                                            <?= format_currency($package['price']) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($package['description']): ?>
                                        <p class="package-description"><?= htmlspecialchars($package['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($package['features']): ?>
                                        <ul class="package-features">
                                            <?php
                                            $features = json_decode($package['features'], true);
                                            if (is_array($features)) {
                                                foreach ($features as $feature) {
                                                    echo '<li>' . htmlspecialchars($feature) . '</li>';
                                                }
                                            }
                                            ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="<?= base_url('staff/dashboard/license/upgrade') ?>" style="margin-top: 1rem;">
                                        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                        <button type="submit" class="btn-upgrade <?= $isLower ? 'btn-downgrade' : '' ?>" 
                                                <?= $buttonDisabled ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($buttonText) ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('License key copied to clipboard!');
    }, function(err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('License key copied to clipboard!');
    });
}
</script>

