<?php

$tabs = [
    'general' => 'General',
    'branding' => 'Branding',
    'pos' => 'POS Settings',
    'hotel' => 'Hotel Settings',
    'website' => 'Website Settings',
    'inventory' => 'Inventory Settings',
    'staff' => 'Staff & Roles',
    'notifications' => 'Notifications',
    'integrations' => 'Integrations',
    'payment-gateway' => 'Payment Gateways',
    'security' => 'Security',
];

$activeTab = $_GET['tab'] ?? 'branding';
// Get the actual user's role config, not hardcoded admin
use App\Support\Auth;
$user = Auth::user();
$userRoleKey = $user['role_key'] ?? ($user['role'] ?? null);
$allRoles = config('roles', []);
// If user has deprecated 'admin' role, use 'director' config instead
if ($userRoleKey === 'admin') {
    $roleConfig = $allRoles['director'] ?? [];
} else {
    $roleConfig = $allRoles[$userRoleKey] ?? [];
}

ob_start();
?>
<section class="settings-shell dashboard-settings">
    <div class="settings-sidebar">
        <h2>Settings</h2>
        <p class="muted">Control every global configuration from one place.</p>
        <?php foreach ($tabs as $key => $label): ?>
            <button class="settings-tab<?= $activeTab === $key ? ' active' : ''; ?>" data-settings-tab="<?= $key ?>">
                <?= htmlspecialchars($label); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="settings-panels">
        <?php foreach ($tabs as $key => $label): ?>
            <?php if ($key === 'payment-gateway'): ?>
                <div class="settings-panel<?= $activeTab === $key ? ' active' : ''; ?>" data-settings-panel="<?= $key ?>">
                    <header>
                        <h3>Payment Gateways</h3>
                        <p>Configure payment methods for your platform.</p>
                    </header>
                    <?php if (isset($_GET['success']) && $_GET['tab'] === $key): ?>
                        <div class="alert alert-success" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
                            <?= htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $gateway = $_GET['gateway'] ?? 'mpesa';
                    $gatewaySettings = $settings['payment_gateways'][$gateway] ?? [];
                    $isInSettings = true;
                    include view_path('dashboard/payment-gateway/gateway-form.php');
                    ?>
                </div>
            <?php else: ?>
                <form class="settings-panel<?= $activeTab === $key ? ' active' : ''; ?>" data-settings-panel="<?= $key ?>" method="post" action="<?= base_url('staff/admin/settings'); ?>"<?= $key === 'website' ? ' enctype="multipart/form-data"' : ''; ?>>
                    <input type="hidden" name="group" value="<?= $key; ?>">
                    <header>
                        <h3><?= htmlspecialchars($label); ?></h3>
                        <p>Update <?= strtolower($label); ?> configuration.</p>
                    </header>
                    <?php if (isset($_GET['success']) && $_GET['tab'] === $key): ?>
                        <div class="alert alert-success" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 0.5rem;">
                            <?= htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="panel-fields">
                        <?php include view_path("admin/settings/partials/{$key}.php"); ?>
                    </div>
                    <div class="panel-actions">
                        <button class="btn btn-primary" type="submit">Save <?= htmlspecialchars($label); ?></button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<style>
.payment-gateway-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-top: 1rem;
}

.gateway-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    overflow-x: auto;
    scrollbar-width: thin;
}

.gateway-tabs::-webkit-scrollbar {
    height: 6px;
}

.gateway-tabs::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.gateway-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.gateway-tab:hover {
    background: #e9ecef;
    color: #495057;
}

.gateway-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: white;
    font-weight: 500;
}

.gateway-icon {
    font-size: 1.2rem;
}

.gateway-content {
    padding: 2rem;
}

.gateway-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.gateway-info h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: #212529;
}

.gateway-description {
    color: #6c757d;
    margin: 0;
    font-size: 0.95rem;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"] {
    position: relative;
    width: 48px;
    height: 24px;
    appearance: none;
    background: #ccc;
    border-radius: 12px;
    transition: background 0.3s;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"]:checked {
    background: #28a745;
}

.toggle-switch input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: left 0.3s;
}

.toggle-switch input[type="checkbox"]:checked::before {
    left: 26px;
}

.toggle-label {
    font-weight: 500;
    color: #495057;
}

.gateway-fields {
    margin-bottom: 2rem;
}

.gateway-fields fieldset {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 0;
}

.gateway-fields legend {
    font-weight: 600;
    color: #495057;
    padding: 0 0.75rem;
    font-size: 1.1rem;
}

.gateway-fields label {
    display: block;
    margin-bottom: 1.25rem;
}

.gateway-fields label:last-child {
    margin-bottom: 0;
}

.gateway-fields label span {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.gateway-fields input[type="text"],
.gateway-fields input[type="password"],
.gateway-fields input[type="number"],
.gateway-fields select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.gateway-fields input:focus,
.gateway-fields select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.gateway-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.gateway-actions .btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    font-size: 0.95rem;
}

.gateway-actions .btn-primary {
    background: #007bff;
    color: white;
}

.gateway-actions .btn-primary:hover {
    background: #0056b3;
}

.gateway-actions .btn-secondary {
    background: #6c757d;
    color: white;
}

.gateway-actions .btn-secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .gateway-header {
        flex-direction: column;
        gap: 1rem;
    }

    .gateway-tabs {
        flex-wrap: nowrap;
    }

    .gateway-tab {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }

    .gateway-content {
        padding: 1.5rem;
    }
}
</style>
<script>
    const tabButtons = document.querySelectorAll('[data-settings-tab]');
    const panels = document.querySelectorAll('[data-settings-panel]');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const target = button.dataset.settingsTab;

            tabButtons.forEach(btn => btn.classList.toggle('active', btn === button));
            panels.forEach(panel => {
                panel.classList.toggle('active', panel.dataset.settingsPanel === target);
            });

            const url = new URL(window.location.href);
            url.searchParams.set('tab', target);
            window.history.replaceState({}, '', url);
        });
    });
</script>
<?php
$slot = ob_get_clean();

include view_path('layouts/dashboard.php');

?>

