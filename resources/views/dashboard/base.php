<?php
$pageTitle = ($roleConfig['label'] ?? 'Dashboard') . ' | Hotela';
$dashboardData = $contentData ?? [];

// SECURITY: Never allow 'user' to be extracted from contentData - it must come from Auth::user() only
// This prevents session hijacking by overwriting the $user variable
$protectedKeys = ['user', 'user_id', 'current_user', 'logged_in_user'];
$safeContentData = [];
if (is_array($contentData ?? [])) {
    foreach ($contentData as $key => $value) {
        // Block extraction of protected keys that could affect authentication
        if (!in_array(strtolower($key), array_map('strtolower', $protectedKeys), true)) {
            $safeContentData[$key] = $value;
        }
    }
    extract($safeContentData, EXTR_SKIP);
}

ob_start();
include view_path($contentView . '.php');
$slot = ob_get_clean();

include view_path('layouts/dashboard.php');

