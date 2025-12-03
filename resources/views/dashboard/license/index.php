<?php
// This view is included via dashboard/base.php
// Variables available: $license, $validation
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-key"></i> License Management</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <h5><i class="fas fa-check-circle"></i> Success</h5>
                            <p class="mb-0"><?= htmlspecialchars($_GET['success']) ?></p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
                            <p class="mb-0"><?= htmlspecialchars($_GET['error']) ?></p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($validation['valid']): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> License Active</h5>
                            <p class="mb-0"><?= htmlspecialchars($validation['message']) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> License Issue</h5>
                            <p class="mb-0"><?= htmlspecialchars($validation['message']) ?></p>
                            <?php if (isset($validation['expires_at'])): ?>
                                <p class="mb-0 mt-2"><strong>Expired:</strong> <?= date('F j, Y g:i A', strtotime($validation['expires_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($license): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Current License</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">License Key</th>
                                        <td><code><?= htmlspecialchars($license['license_key']) ?></code></td>
                                    </tr>
                                    <tr>
                                        <th>Plan Type</th>
                                        <td><span class="badge bg-info"><?= strtoupper($license['plan_type']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?= $license['status'] === 'active' ? 'success' : ($license['status'] === 'trial' ? 'warning' : 'danger') ?>">
                                                <?= strtoupper($license['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if ($license['expires_at']): ?>
                                        <tr>
                                            <th>Expires At</th>
                                            <td><?= date('F j, Y g:i A', strtotime($license['expires_at'])) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($license['last_verified_at']): ?>
                                        <tr>
                                            <th>Last Verified</th>
                                            <td><?= date('F j, Y g:i A', strtotime($license['last_verified_at'])) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Activate or Renew License</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$validation['valid']): ?>
                                <div class="alert alert-info mb-3">
                                    <strong>Need a license?</strong> Click the "Fetch License" button below to request a license key from the system administrator. The license will be sent to your email automatically.
                                </div>
                                
                                <form method="POST" action="<?= base_url('staff/dashboard/license/fetch'); ?>" class="mb-4">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-download"></i> Fetch License
                                    </button>
                                    <small class="form-text text-muted d-block mt-2">Request a license key to be sent to your registered email address.</small>
                                </form>
                                
                                <hr class="my-4">
                            <?php endif; ?>
                            
                            <form method="POST" action="/staff/dashboard/license/activate">
                                <div class="mb-3">
                                    <label for="license_key" class="form-label">License Key</label>
                                    <input type="text" class="form-control" id="license_key" name="license_key" 
                                           placeholder="Enter your license key" required>
                                    <small class="form-text text-muted">Enter your license key to activate or renew your subscription.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hardware_fingerprint" class="form-label">Hardware Fingerprint (Optional)</label>
                                    <input type="text" class="form-control" id="hardware_fingerprint" name="hardware_fingerprint" 
                                           placeholder="Auto-generated if left empty">
                                    <small class="form-text text-muted">Hardware fingerprint for license binding. Leave empty to auto-generate.</small>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Activate License
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

