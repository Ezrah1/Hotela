<?php
$pageTitle = ($roleConfig['label'] ?? 'Dashboard') . ' | Hotela';
$dashboardData = $contentData ?? [];

// Extract contentData variables for use in the included view
if (is_array($contentData ?? [])) {
    extract($contentData, EXTR_SKIP);
}

ob_start();
include view_path($contentView . '.php');
$slot = ob_get_clean();

include view_path('layouts/dashboard.php');

