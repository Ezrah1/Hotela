<?php

$tabs = [
    'branding' => 'Branding',
    'pos' => 'POS Settings',
    'hotel' => 'Hotel Settings',
    'website' => 'Website Settings',
    'inventory' => 'Inventory Settings',
    'staff' => 'Staff & Roles',
    'notifications' => 'Notifications',
    'integrations' => 'Integrations',
    'security' => 'Security',
];

$activeTab = $_GET['tab'] ?? 'branding';
$roleConfig = config('roles.admin');

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
            <form class="settings-panel<?= $activeTab === $key ? ' active' : ''; ?>" data-settings-panel="<?= $key ?>" method="post" action="<?= base_url('admin/settings'); ?>">
                <input type="hidden" name="group" value="<?= $key; ?>">
                <header>
                    <h3><?= htmlspecialchars($label); ?></h3>
                    <p>Update <?= strtolower($label); ?> configuration.</p>
                </header>
                <div class="panel-fields">
                    <?php include view_path("admin/settings/partials/{$key}.php"); ?>
                </div>
                <div class="panel-actions">
                    <button class="btn btn-primary" type="submit">Save <?= htmlspecialchars($label); ?></button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
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

