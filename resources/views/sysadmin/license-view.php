<?php
$pageTitle = 'License Details';
ob_start();
?>

<div class="sysadmin-page-header">
    <div>
        <a href="<?= base_url('sysadmin/licenses'); ?>" class="btn-link" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Licenses
        </a>
        <h2>License Details</h2>
        <p class="page-subtitle">View complete license information and activation details</p>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'revoked'): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 1rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
        <strong>License Revoked</strong> - The license has been successfully revoked due to terms of use violation.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 1.5rem; padding: 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
        <?php
        $errors = [
            'reason_required' => 'Revocation reason is required.',
            'invalid_id' => 'Invalid license ID.',
            'not_found' => 'License not found.',
            'already_revoked' => 'This license has already been revoked.',
            'revoke_failed' => 'Failed to revoke license. Please try again.',
        ];
        echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<div class="sysadmin-card">
    <div class="card-header">
        <h3>License Information</h3>
    </div>
    <div class="card-body">
        <div class="license-details-grid">
            <div class="info-item">
                <div class="info-label">License Status</div>
                <div class="info-value">
                    <span class="badge badge-<?= $license['status'] === 'active' ? 'success' : ($license['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                        <?= ucfirst($license['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Installation ID</div>
                <div class="info-value">
                    <code style="background: #f1f5f9; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; word-break: break-all;">
                        <?= htmlspecialchars($license['installation_id']); ?>
                    </code>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">License Key</div>
                <div class="info-value">
                    <code style="background: #f1f5f9; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; word-break: break-all; display: block;">
                        <?= htmlspecialchars($license['license_key']); ?>
                    </code>
                    <button onclick="copyToClipboard('<?= htmlspecialchars($license['license_key']); ?>')" class="btn btn-sm btn-outline" style="margin-top: 0.5rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copy Key
                    </button>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Activated At</div>
                <div class="info-value"><?= date('F j, Y g:i A', strtotime($license['activated_at'])); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Expires At</div>
                <div class="info-value">
                    <?= $license['expires_at'] ? date('F j, Y g:i A', strtotime($license['expires_at'])) : 'Never'; ?>
                    <?php if ($license['expires_at']): ?>
                        <?php
                        $expiresAt = new \DateTime($license['expires_at']);
                        $now = new \DateTime();
                        $daysLeft = $now->diff($expiresAt)->days;
                        if ($expiresAt > $now):
                        ?>
                            <span class="badge badge-info" style="margin-left: 0.5rem;">
                                <?= $daysLeft; ?> days remaining
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Last Verified</div>
                <div class="info-value">
                    <?= $license['last_verified_at'] ? date('F j, Y g:i A', strtotime($license['last_verified_at'])) : 'Never'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Package Information</h3>
    </div>
    <div class="card-body">
        <?php if ($license['package_id'] && $license['package_name']): ?>
            <div class="license-details-grid">
                <div class="info-item">
                    <div class="info-label">Current Package</div>
                    <div class="info-value">
                        <strong style="font-size: 1.125rem; color: #1e293b;"><?= htmlspecialchars($license['package_name']); ?></strong>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Package Price</div>
                    <div class="info-value">
                        <?= htmlspecialchars($license['package_currency'] ?? 'USD'); ?> <?= number_format($license['package_price'] ?? 0, 2); ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Duration</div>
                    <div class="info-value"><?= $license['package_duration'] ?? 12; ?> months</div>
                </div>
                
                <?php if ($license['package_features']): ?>
                    <?php $features = json_decode($license['package_features'], true); ?>
                    <?php if (is_array($features) && !empty($features)): ?>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-label">Package Features</div>
                            <div class="info-value">
                                <ul style="list-style: none; padding: 0; margin: 0.5rem 0 0 0;">
                                    <?php foreach ($features as $feature): ?>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <?= htmlspecialchars($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="info-item">
                <div class="info-label">Current Package</div>
                <div class="info-value">
                    <span class="badge badge-warning">No Package Assigned</span>
                    <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                        This license was generated manually without a package. Assign a package to enable upgrade options.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Tenant & Director Information</h3>
    </div>
    <div class="card-body">
        <div class="license-details-grid">
            <div class="info-item">
                <div class="info-label">Tenant Name</div>
                <div class="info-value">
                    <?php if ($license['tenant_id']): ?>
                        <a href="<?= base_url('sysadmin/tenants/view?id=' . $license['tenant_id']); ?>" style="color: #667eea; text-decoration: none;">
                            <?= htmlspecialchars($license['tenant_name'] ?? 'N/A'); ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($license['tenant_name'] ?? 'N/A'); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Tenant Domain</div>
                <div class="info-value">
                    <code><?= htmlspecialchars($license['tenant_domain'] ?? 'N/A'); ?></code>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Director Name</div>
                <div class="info-value"><?= htmlspecialchars($license['director_name'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Director Email</div>
                <div class="info-value">
                    <a href="mailto:<?= htmlspecialchars($license['director_email'] ?? ''); ?>">
                        <?= htmlspecialchars($license['director_email'] ?? 'N/A'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($packages)): ?>
<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Upgrade License Package</h3>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <?php
                $errors = [
                    'missing_data' => 'Please select a package.',
                    'package_not_found' => 'Package not found.',
                    'tenant_not_found' => 'Tenant not found.',
                ];
                echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'upgraded'): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                License upgraded successfully! The expiration date has been extended based on the new package duration.
            </div>
        <?php endif; ?>
        
        <p style="color: #64748b; margin-bottom: 1.5rem;">
            Upgrade or downgrade this license to different packages. The expiration date will be extended based on the selected package duration.
        </p>
        
        <?php
        // Get current package price for comparison
        $currentPackagePrice = null;
        if ($license && isset($license['package_price'])) {
            $currentPackagePrice = (float)$license['package_price'];
        }
        ?>
        
        <div class="upgrade-packages-grid">
            <?php foreach ($packages as $pkg): ?>
                <?php
                $packagePrice = (float)$pkg['price'];
                $isCurrentPackage = ($pkg['id'] == ($license['package_id'] ?? null));
                $isHigher = $currentPackagePrice !== null && $packagePrice > $currentPackagePrice;
                $isLower = $currentPackagePrice !== null && $packagePrice < $currentPackagePrice;
                
                // Determine button text and action
                if ($isCurrentPackage) {
                    $buttonText = 'Current Package';
                    $buttonDisabled = true;
                    $buttonClass = 'btn btn-outline';
                    $buttonOnClick = '';
                } elseif ($isHigher) {
                    $buttonText = 'Upgrade to This Package';
                    $buttonDisabled = false;
                    $buttonClass = 'btn btn-primary btn-full';
                    $buttonOnClick = "return confirm('Upgrade license to " . htmlspecialchars($pkg['name'], ENT_QUOTES) . "? This will extend the expiration date by " . $pkg['duration_months'] . " months.')";
                } elseif ($isLower) {
                    $buttonText = 'Downgrade to This Package';
                    $buttonDisabled = false;
                    $buttonClass = 'btn btn-warning btn-full';
                    $buttonOnClick = "return confirm('Downgrade license to " . htmlspecialchars($pkg['name'], ENT_QUOTES) . "? This will extend the expiration date by " . $pkg['duration_months'] . " months.')";
                } else {
                    $buttonText = 'Switch to This Package';
                    $buttonDisabled = false;
                    $buttonClass = 'btn btn-primary btn-full';
                    $buttonOnClick = "return confirm('Switch license to " . htmlspecialchars($pkg['name'], ENT_QUOTES) . "? This will extend the expiration date by " . $pkg['duration_months'] . " months.')";
                }
                ?>
                
                <?php if ($isCurrentPackage): ?>
                    <div class="upgrade-package-card current">
                        <div class="package-badge-current">Current Package</div>
                        <h4><?= htmlspecialchars($pkg['name']); ?></h4>
                        <div class="package-price-small">
                            <span class="currency"><?= htmlspecialchars($pkg['currency']); ?></span>
                            <span class="amount"><?= number_format($pkg['price'], 2); ?></span>
                            <span class="duration">/ <?= $pkg['duration_months']; ?> months</span>
                        </div>
                        <button class="<?= $buttonClass; ?>" <?= $buttonDisabled ? 'disabled' : ''; ?>>
                            <?= htmlspecialchars($buttonText); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="upgrade-package-card">
                        <h4><?= htmlspecialchars($pkg['name']); ?></h4>
                        <div class="package-price-small">
                            <span class="currency"><?= htmlspecialchars($pkg['currency']); ?></span>
                            <span class="amount"><?= number_format($pkg['price'], 2); ?></span>
                            <span class="duration">/ <?= $pkg['duration_months']; ?> months</span>
                        </div>
                        <?php if ($pkg['description']): ?>
                            <p class="package-desc-small"><?= htmlspecialchars($pkg['description']); ?></p>
                        <?php endif; ?>
                        <form method="POST" action="<?= base_url('sysadmin/licenses/upgrade'); ?>" style="margin-top: auto;">
                            <input type="hidden" name="license_id" value="<?= $license['id']; ?>">
                            <input type="hidden" name="package_id" value="<?= $pkg['id']; ?>">
                            <button type="submit" class="<?= $buttonClass; ?>" <?= $buttonDisabled ? 'disabled' : ''; ?> onclick="<?= $buttonOnClick; ?>">
                                <?= htmlspecialchars($buttonText); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="sysadmin-card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>Actions</h3>
    </div>
    <div class="card-body">
        <div class="action-buttons" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <form method="POST" action="<?= base_url('sysadmin/licenses/send'); ?>" style="display: inline;">
                <input type="hidden" name="license_id" value="<?= $license['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Resend License Email
                </button>
            </form>
            
            <?php if ($license['tenant_id']): ?>
                <a href="<?= base_url('sysadmin/tenants/view?id=' . $license['tenant_id']); ?>" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    View Tenant
                </a>
            <?php endif; ?>
            
            <?php if ($license['status'] !== 'revoked'): ?>
                <button type="button" class="btn btn-danger" onclick="openRevokeModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Revoke License
                </button>
            <?php else: ?>
                <div style="padding: 0.75rem 1rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem;">
                    <strong>License Revoked</strong>
                    <?php if ($license['revocation_reason']): ?>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">
                            <strong>Reason:</strong> <?= htmlspecialchars($license['revocation_reason']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($license['revoked_at']): ?>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">
                            <strong>Revoked on:</strong> <?= date('F j, Y g:i A', strtotime($license['revoked_at'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Revoke License Modal -->
<div id="revokeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 0.5rem; padding: 2rem; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-top: 0; color: #dc2626;">Revoke License</h3>
        <p style="color: #64748b; margin-bottom: 1.5rem;">
            This action will immediately revoke the license due to terms of use violation. This action cannot be undone.
        </p>
        
        <form method="POST" action="<?= base_url('sysadmin/licenses/revoke'); ?>" id="revokeForm">
            <input type="hidden" name="license_id" value="<?= $license['id']; ?>">
            
            <div style="margin-bottom: 1.5rem;">
                <label for="revocation_reason" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Reason for Revocation <span style="color: #dc2626;">*</span>
                </label>
                <textarea 
                    id="revocation_reason" 
                    name="reason" 
                    required 
                    rows="5" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-family: inherit; font-size: 0.875rem;"
                    placeholder="Enter the reason for revoking this license (e.g., Terms of use violation, payment issues, etc.)"
                ></textarea>
                <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                    This reason will be recorded and visible in the license history.
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeRevokeModal()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger" style="padding: 0.75rem 1.5rem;">
                    Confirm Revocation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRevokeModal() {
    document.getElementById('revokeModal').style.display = 'flex';
}

function closeRevokeModal() {
    document.getElementById('revokeModal').style.display = 'none';
    document.getElementById('revokeForm').reset();
}

// Close modal when clicking outside
document.getElementById('revokeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRevokeModal();
    }
});

// Prevent form submission if reason is empty
document.getElementById('revokeForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('revocation_reason').value.trim();
    if (!reason) {
        e.preventDefault();
        alert('Please enter a reason for revoking the license.');
        return false;
    }
    
    if (!confirm('Are you sure you want to revoke this license? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>

<style>
.license-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-item {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.info-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 1rem;
    color: #1e293b;
    word-break: break-word;
}

.btn-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.btn-link:hover {
    color: #764ba2;
}

code {
    font-family: 'Courier New', monospace;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
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

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.badge-warning {
    background: #fffbeb;
    color: #d97706;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.badge-success {
    background: #f0fdf4;
    color: #166534;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.badge-danger {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.badge-info {
    background: #eff6ff;
    color: #2563eb;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}
</style>

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

<?php
$slot = ob_get_clean();
include view_path('layouts/sysadmin.php');
?>

