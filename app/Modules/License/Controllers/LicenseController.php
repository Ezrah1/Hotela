<?php

namespace App\Modules\License\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\LicensingService;
use App\Repositories\SystemLicenseRepository;

class LicenseController extends Controller
{
    protected LicensingService $licensingService;
    protected SystemLicenseRepository $licenseRepo;

    public function __construct()
    {
        $this->licensingService = new LicensingService();
        $this->licenseRepo = new SystemLicenseRepository();
    }

    public function index(Request $request): void
    {
        \App\Support\Auth::requireRoles();
        
        $license = $this->licenseRepo->getCurrent();
        $validation = $this->licensingService->validate();

        $user = \App\Support\Auth::user();
        $role = $user['role'] ?? $user['role_key'] ?? 'admin';
        
        $this->view('dashboard/base', [
            'user' => $user,
            'roleConfig' => ['label' => 'License Management'],
            'contentView' => 'dashboard/license/index',
            'contentData' => [
                'license' => $license,
                'validation' => $validation,
            ],
        ]);
    }

    public function activate(Request $request): void
    {
        if ($request->method() !== 'POST') {
            header('Location: /dashboard/license');
            exit;
        }

        $licenseKey = $request->input('license_key', '');
        $hardwareFingerprint = $request->input('hardware_fingerprint', '');

        if (empty($licenseKey)) {
            show_message('error', 'Error', 'License key is required.', '/dashboard/license');
            return;
        }

        $result = $this->licensingService->activate($licenseKey, $hardwareFingerprint);

        if ($result['valid']) {
            show_message('success', 'Success', $result['message'], '/dashboard/license', 3);
        } else {
            show_message('error', 'Error', $result['message'] ?? 'Failed to activate license.', '/dashboard/license');
        }
    }
}

