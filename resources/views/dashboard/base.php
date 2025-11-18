<?php
$pageTitle = ($roleConfig['label'] ?? 'Dashboard') . ' | Hotela';
$dashboardData = $contentData;

ob_start();
include view_path($contentView . '.php');
$slot = ob_get_clean();

include view_path('layouts/dashboard.php');

