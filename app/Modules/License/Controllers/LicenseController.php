<?php

namespace App\Modules\License\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\LicensingService;
use App\Repositories\SystemLicenseRepository;
use App\Repositories\LicenseRepository;
use App\Repositories\TenantRepository;
use App\Services\License\LicenseGenerator;
use App\Services\Email\EmailService;

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
            header('Location: ' . base_url('staff/dashboard/license'));
            exit;
        }

        $licenseKey = $request->input('license_key', '');
        $hardwareFingerprint = $request->input('hardware_fingerprint', '');

        if (empty($licenseKey)) {
            header('Location: ' . base_url('staff/dashboard/license?error=' . urlencode('License key is required.')));
            exit;
        }

        $result = $this->licensingService->activate($licenseKey, $hardwareFingerprint);

        if ($result['valid']) {
            // Redirect to license page with success message
            header('Location: ' . base_url('staff/dashboard/license?success=' . urlencode($result['message'] ?? 'License activated successfully.')));
            exit;
        } else {
            // Redirect to license page with error message
            header('Location: ' . base_url('staff/dashboard/license?error=' . urlencode($result['message'] ?? 'Failed to activate license.')));
            exit;
        }
    }
    
    public function fetchLicense(Request $request): void
    {
        \App\Support\Auth::requireRoles(['director']);
        
        $user = \App\Support\Auth::user();
        $userId = (int)$user['id'];
        $userEmail = $user['email'];
        $tenantId = $user['tenant_id'] ?? null;
        
        if (!$tenantId) {
            header('Location: ' . base_url('staff/dashboard/license?error=' . urlencode('Unable to determine your tenant. Please contact support.')));
            exit;
        }
        
        // Get tenant information
        $tenantRepo = new TenantRepository();
        $tenant = $tenantRepo->findById($tenantId);
        
        if (!$tenant) {
            header('Location: ' . base_url('staff/dashboard/license?error=' . urlencode('Tenant not found. Please contact support.')));
            exit;
        }
        
        // Check if license already exists
        $licenseRepo = new LicenseRepository();
        $existingLicense = $licenseRepo->getActivation();
        
        if ($existingLicense && $existingLicense['status'] === 'active') {
            // Resend existing license
            $this->sendLicenseEmail($user, $tenant, $existingLicense['license_key'], $existingLicense['signed_token']);
            header('Location: ' . base_url('staff/dashboard/license?success=' . urlencode('Your license key has been sent to your email address.')));
            exit;
        }
        
        // Generate new license
        $licenseKey = LicenseGenerator::generate();
        $installationId = $licenseRepo->getInstallationId();
        $signedToken = LicenseGenerator::signToken($licenseKey, $installationId, $userEmail);
        
        // Calculate expiration (1 year from now)
        $expiresAt = new \DateTime();
        $expiresAt->modify('+1 year');
        
        // Activate license
        $licenseRepo->activate(
            $installationId,
            $userEmail,
            $userId,
            $licenseKey,
            $signedToken,
            $expiresAt
        );
        
        // Send email
        $this->sendLicenseEmail($user, $tenant, $licenseKey, $signedToken);
        
        // Redirect to license page with success message
        header('Location: ' . base_url('staff/dashboard/license?success=' . urlencode('A new license key has been generated and sent to your email address.')));
        exit;
    }
    
    protected function sendLicenseEmail(array $user, array $tenant, string $licenseKey, string $signedToken): void
    {
        $emailService = new EmailService();
        
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $activationUrl = $scheme . '://' . $host . base_url('staff/dashboard/license');
        
        $subject = 'Your Hotela License Key';
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
                .license-box { background: white; border: 2px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; }
                .license-key { font-size: 18px; font-weight: bold; color: #667eea; letter-spacing: 2px; font-family: monospace; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Your Hotela License Key</h1>
                </div>
                <div class='content'>
                    <p>Dear {$user['name']},</p>
                    <p>Your license key for <strong>{$tenant['name']}</strong> has been generated and is ready for activation.</p>
                    
                    <div class='license-box'>
                        <p style='margin: 0 0 10px 0; color: #666;'>License Key:</p>
                        <div class='license-key'>{$licenseKey}</div>
                    </div>
                    
                    <p>To activate your license:</p>
                    <ol>
                        <li>Log in to your Hotela dashboard</li>
                        <li>Navigate to the License page</li>
                        <li>Enter your license key and click 'Activate'</li>
                    </ol>
                    
                    <p style='text-align: center;'>
                        <a href='{$activationUrl}' class='button'>Activate License</a>
                    </p>
                    
                    <p><strong>Important:</strong> Keep this license key secure. Do not share it publicly.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Hotela License Management System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        try {
            $emailService->send(
                $user['email'],
                $subject,
                $body,
                $user['name']
            );
        } catch (\Exception $e) {
            error_log("Failed to send license email: " . $e->getMessage());
        }
    }

    public function manage(Request $request): void
    {
        \App\Support\Auth::requireRoles(['director']);
        
        $user = \App\Support\Auth::user();
        $userId = (int)$user['id'];
        
        $licenseRepo = new LicenseRepository();
        $license = $licenseRepo->getByDirectorUserId($userId);
        
        // Get all available packages for upgrade
        $packageRepo = new \App\Repositories\LicensePackageRepository();
        $packages = $packageRepo->all();
        
        // Filter out inactive packages
        $packages = array_filter($packages, function($pkg) {
            return $pkg['is_active'] == 1;
        });
        
        $this->view('dashboard/base', [
            'user' => $user,
            'roleConfig' => ['label' => 'License Management'],
            'contentView' => 'dashboard/license/manage',
            'contentData' => [
                'license' => $license,
                'packages' => $packages,
            ],
        ]);
    }

    public function upgrade(Request $request): void
    {
        \App\Support\Auth::requireRoles(['director']);
        
        if ($request->method() !== 'POST') {
            header('Location: ' . base_url('staff/dashboard/license/manage'));
            exit;
        }
        
        $user = \App\Support\Auth::user();
        $userId = (int)$user['id'];
        $packageId = (int)$request->input('package_id', 0);
        
        if (!$packageId) {
            header('Location: ' . base_url('staff/dashboard/license/manage?error=' . urlencode('Please select a package to upgrade to.')));
            exit;
        }
        
        $licenseRepo = new LicenseRepository();
        $license = $licenseRepo->getByDirectorUserId($userId);
        
        if (!$license) {
            header('Location: ' . base_url('staff/dashboard/license/manage?error=' . urlencode('No active license found.')));
            exit;
        }
        
        $packageRepo = new \App\Repositories\LicensePackageRepository();
        $package = $packageRepo->findById($packageId);
        
        if (!$package || !$package['is_active']) {
            header('Location: ' . base_url('staff/dashboard/license/manage?error=' . urlencode('Selected package not found or inactive.')));
            exit;
        }
        
        // Calculate new expiration date
        $expiresAt = new \DateTime();
        if ($license['expires_at']) {
            $currentExpiry = new \DateTime($license['expires_at']);
            // If current license hasn't expired, extend from current expiry
            if ($currentExpiry > new \DateTime()) {
                $expiresAt = $currentExpiry;
            }
        }
        
        // Add package duration
        $expiresAt->modify('+' . $package['duration_months'] . ' months');
        
        // Create payment record
        $paymentRepo = new \App\Repositories\LicensePaymentRepository();
        $paymentId = $paymentRepo->create([
            'license_id' => $license['id'],
            'package_id' => $packageId,
            'amount' => $package['price'],
            'status' => 'pending',
            'gateway' => 'manual',
        ]);
        
        // Update license with new package
        $licenseRepo->updatePackage($license['id'], $packageId, $paymentId, $expiresAt);
        
        // Update payment status to completed (assuming manual payment for now)
        $paymentRepo->updateStatus($paymentId, 'completed');
        
        header('Location: ' . base_url('staff/dashboard/license/manage?success=' . urlencode('License upgraded successfully! Your new expiration date is ' . $expiresAt->format('F j, Y'))));
        exit;
    }
}

