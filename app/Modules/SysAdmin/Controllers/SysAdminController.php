<?php

namespace App\Modules\SysAdmin\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SystemAdminRepository;
use App\Repositories\TenantRepository;
use App\Repositories\LicenseRepository;
use App\Repositories\LicensePackageRepository;
use App\Repositories\LicensePaymentRepository;
use App\Repositories\SystemAdminRepository as SysAdminRepo;
use App\Services\License\LicenseGenerator;
use App\Services\Email\EmailService;

class SysAdminController extends Controller
{
    protected SystemAdminRepository $sysAdminRepo;
    protected TenantRepository $tenantRepo;
    protected LicenseRepository $licenseRepo;
    protected LicensePackageRepository $packageRepo;

    public function __construct()
    {
        $this->sysAdminRepo = new SystemAdminRepository();
        $this->tenantRepo = new TenantRepository();
        $this->licenseRepo = new LicenseRepository();
        $this->packageRepo = new LicensePackageRepository();
    }

    public function dashboard(): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        // Get system stats
        $tenants = $this->tenantRepo->all();
        $currentLicense = $this->licenseRepo->getActivation();
        $activeLicenses = $currentLicense ? 1 : 0;
        
        // Get recent system admin actions
        $recentActions = $this->getRecentActions(10);
        
        // Get system health
        $health = $this->getSystemHealth();

        $this->view('sysadmin/dashboard', [
            'pageTitle' => 'System Control Center',
            'admin' => $admin,
            'stats' => [
                'tenants' => count($tenants),
                'active_licenses' => $activeLicenses,
                'uptime' => $this->calculateUptime(),
                'pending_updates' => 0,
            ],
            'recentActions' => $recentActions,
            'health' => $health,
        ]);
    }
    
    public function tenants(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST' && $request->input('action') === 'create') {
            $name = trim($request->input('name'));
            $domain = trim($request->input('domain'));
            $contactEmail = trim($request->input('contact_email'));
            $contactPhone = trim($request->input('contact_phone'));
            
            if (empty($name)) {
                header('Location: ' . base_url('sysadmin/tenants?error=name_required'));
                return;
            }
            
            // Check if domain already exists
            if ($domain) {
                $db = db();
                $checkStmt = $db->prepare('SELECT id FROM tenants WHERE domain = :domain');
                $checkStmt->execute(['domain' => $domain]);
                if ($checkStmt->fetch()) {
                    header('Location: ' . base_url('sysadmin/tenants?error=domain_exists'));
                    return;
                }
            }
            
            $tenantId = $this->tenantRepo->create([
                'name' => $name,
                'domain' => $domain ?: null,
                'contact_email' => $contactEmail ?: null,
                'contact_phone' => $contactPhone ?: null,
                'status' => 'active',
            ]);
            
            // Log action
            $this->sysAdminRepo->logAction(
                $admin['id'],
                'create_tenant',
                'tenant',
                $tenantId,
                ['name' => $name, 'domain' => $domain]
            );
            
            header('Location: ' . base_url('sysadmin/tenants?success=tenant_created'));
            return;
        }
        
        $tenants = $this->tenantRepo->all();
        
        // Get stats for each tenant
        $tenantsWithStats = [];
        foreach ($tenants as $tenant) {
            $stats = $this->tenantRepo->getStats($tenant['id']);
            $tenantsWithStats[] = array_merge($tenant, ['stats' => $stats]);
        }

        $this->view('sysadmin/tenants', [
            'pageTitle' => 'Tenant Management',
            'admin' => $admin,
            'tenants' => $tenantsWithStats,
        ]);
    }
    
    public function viewTenant(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        $tenantId = (int)$request->input('id');
        
        if (!$tenantId) {
            header('Location: ' . base_url('sysadmin/tenants'));
            exit;
        }
        
        $tenant = $this->tenantRepo->findById($tenantId);
        if (!$tenant) {
            header('Location: ' . base_url('sysadmin/tenants?error=not_found'));
            exit;
        }
        
        $stats = $this->tenantRepo->getStats($tenantId);
        
        // Get users for this tenant
        $db = db();
        $usersStmt = $db->prepare('
            SELECT id, name, email, role_key, status, last_login_at, created_at
            FROM users 
            WHERE tenant_id = :tenant_id
            ORDER BY created_at DESC
        ');
        $usersStmt->execute(['tenant_id' => $tenantId]);
        $users = $usersStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get recent bookings
        $bookingsStmt = $db->prepare('
            SELECT id, reference, guest_name, check_in, check_out, status, total_amount, created_at
            FROM reservations 
            WHERE tenant_id = :tenant_id
            ORDER BY created_at DESC
            LIMIT 10
        ');
        $bookingsStmt->execute(['tenant_id' => $tenantId]);
        $recentBookings = $bookingsStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('sysadmin/tenant-view', [
            'pageTitle' => 'Tenant Details: ' . $tenant['name'],
            'admin' => $admin,
            'tenant' => $tenant,
            'stats' => $stats,
            'users' => $users,
            'recentBookings' => $recentBookings,
        ]);
    }
    
    public function licenses(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        // Get all license activations
        $db = db();
        $stmt = $db->query('
            SELECT la.*, u.name AS director_name, u.email AS director_email, t.name AS tenant_name, t.id AS tenant_id
            FROM license_activations la
            LEFT JOIN users u ON u.id = la.director_user_id
            LEFT JOIN tenants t ON t.id = u.tenant_id
            ORDER BY la.activated_at DESC
        ');
        $licenses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add status if missing (for backward compatibility)
        foreach ($licenses as &$license) {
            if (!isset($license['status'])) {
                // Check if expired
                if ($license['expires_at']) {
                    $expiresAt = new \DateTime($license['expires_at']);
                    $license['status'] = $expiresAt < new \DateTime() ? 'expired' : 'active';
                } else {
                    $license['status'] = 'active';
                }
            }
        }
        unset($license);

        $this->view('sysadmin/licenses', [
            'pageTitle' => 'License Management',
            'admin' => $admin,
            'licenses' => $licenses,
        ]);
    }
    
    public function viewLicense(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        $licenseId = (int)$request->input('id');
        
        if (!$licenseId) {
            header('Location: ' . base_url('sysadmin/licenses?error=invalid_id'));
            exit;
        }
        
        $db = db();
        
        // Check if revoked_by column exists
        $columnExists = false;
        try {
            $checkStmt = $db->query("SHOW COLUMNS FROM license_activations LIKE 'revoked_by'");
            $columnExists = $checkStmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Column doesn't exist
        }
        
        // Build query based on whether revoked_by column exists
        if ($columnExists) {
            $stmt = $db->prepare('
                SELECT la.*, 
                       u.name AS director_name, u.email AS director_email, 
                       t.name AS tenant_name, t.id AS tenant_id, t.domain AS tenant_domain,
                       p.id AS package_id, p.name AS package_name, p.price AS package_price, p.currency AS package_currency, p.duration_months AS package_duration, p.features AS package_features,
                       lp.id AS payment_id, lp.payment_status, lp.amount AS payment_amount,
                       sa.username AS revoked_by_name, sa.email AS revoked_by_email
                FROM license_activations la
                LEFT JOIN users u ON u.id = la.director_user_id
                LEFT JOIN tenants t ON t.id = u.tenant_id
                LEFT JOIN license_packages p ON p.id = la.package_id
                LEFT JOIN license_payments lp ON lp.id = la.payment_id
                LEFT JOIN system_admins sa ON sa.id = la.revoked_by
                WHERE la.id = :license_id
            ');
        } else {
            $stmt = $db->prepare('
                SELECT la.*, 
                       u.name AS director_name, u.email AS director_email, 
                       t.name AS tenant_name, t.id AS tenant_id, t.domain AS tenant_domain,
                       p.id AS package_id, p.name AS package_name, p.price AS package_price, p.currency AS package_currency, p.duration_months AS package_duration, p.features AS package_features,
                       lp.id AS payment_id, lp.payment_status, lp.amount AS payment_amount,
                       NULL AS revoked_by_name, NULL AS revoked_by_email
                FROM license_activations la
                LEFT JOIN users u ON u.id = la.director_user_id
                LEFT JOIN tenants t ON t.id = u.tenant_id
                LEFT JOIN license_packages p ON p.id = la.package_id
                LEFT JOIN license_payments lp ON lp.id = la.payment_id
                WHERE la.id = :license_id
            ');
        }
        $stmt->execute(['license_id' => $licenseId]);
        $license = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$license) {
            header('Location: ' . base_url('sysadmin/licenses?error=not_found'));
            exit;
        }
        
        // Add status if missing
        if (!isset($license['status'])) {
            if ($license['expires_at']) {
                $expiresAt = new \DateTime($license['expires_at']);
                $license['status'] = $expiresAt < new \DateTime() ? 'expired' : 'active';
            } else {
                $license['status'] = 'active';
            }
        }
        
        // Get all available packages for upgrade
        $packages = $this->packageRepo->active();
        
        $this->view('sysadmin/license-view', [
            'pageTitle' => 'License Details',
            'admin' => $admin,
            'license' => $license,
            'packages' => $packages,
        ]);
    }
    
    public function upgradeLicense(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST') {
            $licenseId = (int)$request->input('license_id');
            $packageId = (int)$request->input('package_id');
            
            if (!$licenseId || !$packageId) {
                header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=missing_data'));
                exit;
            }
            
            // Get license
            $db = db();
            $licenseStmt = $db->prepare('SELECT * FROM license_activations WHERE id = :id');
            $licenseStmt->execute(['id' => $licenseId]);
            $license = $licenseStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$license) {
                header('Location: ' . base_url('sysadmin/licenses?error=not_found'));
                exit;
            }
            
            // Get package
            $package = $this->packageRepo->findById($packageId);
            if (!$package) {
                header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=package_not_found'));
                exit;
            }
            
            // Get tenant
            $tenantStmt = $db->prepare('
                SELECT t.* FROM tenants t
                INNER JOIN users u ON u.tenant_id = t.id
                WHERE u.id = :director_id
            ');
            $tenantStmt->execute(['director_id' => $license['director_user_id']]);
            $tenant = $tenantStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$tenant) {
                header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=tenant_not_found'));
                exit;
            }
            
            // Create payment record
            $paymentRepo = new \App\Repositories\LicensePaymentRepository();
            $paymentId = $paymentRepo->create([
                'tenant_id' => $tenant['id'],
                'package_id' => $packageId,
                'amount' => $package['price'],
                'currency' => $package['currency'],
                'payment_method' => 'manual',
                'payment_status' => 'completed',
                'payment_reference' => 'UPGRADE-' . time(),
            ]);
            
            // Calculate new expiration based on package duration
            $currentExpires = $license['expires_at'] ? new \DateTime($license['expires_at']) : new \DateTime();
            $newExpires = clone $currentExpires;
            $newExpires->modify('+' . $package['duration_months'] . ' months');
            
            // Update license with new package
            $this->licenseRepo->updatePackage($licenseId, $packageId, $paymentId, $newExpires);
            
            // Log action
            $this->sysAdminRepo->logAction(
                $admin['id'],
                'upgrade_license',
                'license',
                $licenseId,
                [
                    'old_package_id' => $license['package_id'],
                    'new_package_id' => $packageId,
                    'tenant_id' => $tenant['id'],
                    'payment_id' => $paymentId,
                ]
            );
            
            header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&success=upgraded'));
            exit;
        }
        
        header('Location: ' . base_url('sysadmin/licenses'));
        exit;
    }
    
    public function revokeLicense(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() !== 'POST') {
            header('Location: ' . base_url('sysadmin/licenses'));
            exit;
        }
        
        $licenseId = (int)$request->input('license_id');
        $reason = trim($request->input('reason', ''));
        
        if (!$licenseId) {
            header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=invalid_id'));
            exit;
        }
        
        if (empty($reason)) {
            header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=reason_required'));
            exit;
        }
        
        // Get license details for logging
        $db = db();
        $licenseStmt = $db->prepare('
            SELECT la.*, u.name AS director_name, u.email AS director_email, t.name AS tenant_name
            FROM license_activations la
            LEFT JOIN users u ON u.id = la.director_user_id
            LEFT JOIN tenants t ON t.id = u.tenant_id
            WHERE la.id = :license_id
        ');
        $licenseStmt->execute(['license_id' => $licenseId]);
        $license = $licenseStmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$license) {
            header('Location: ' . base_url('sysadmin/licenses?error=not_found'));
            exit;
        }
        
        // Check if already revoked
        if ($license['status'] === 'revoked') {
            header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=already_revoked'));
            exit;
        }
        
        // Revoke the license
        $success = $this->licenseRepo->revoke($licenseId, $reason, $admin['id']);
        
        if (!$success) {
            header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&error=revoke_failed'));
            exit;
        }
        
        // Log action
        $this->sysAdminRepo->logAction(
            $admin['id'],
            'revoke_license',
            'license',
            $licenseId,
            [
                'license_key' => $license['license_key'],
                'director_email' => $license['director_email'] ?? '',
                'tenant_name' => $license['tenant_name'] ?? '',
                'reason' => $reason,
            ]
        );
        
        header('Location: ' . base_url('sysadmin/licenses/view?id=' . $licenseId . '&success=revoked'));
        exit;
    }
    
    public function assignPackage(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST') {
            $tenantId = (int)$request->input('tenant_id');
            $packageId = (int)$request->input('package_id');
            
            if (!$tenantId || !$packageId) {
                header('Location: ' . base_url('sysadmin/tenants/view?id=' . $tenantId . '&error=missing_data'));
                exit;
            }
            
            $tenant = $this->tenantRepo->findById($tenantId);
            $package = $this->packageRepo->findById($packageId);
            
            if (!$tenant || !$package) {
                header('Location: ' . base_url('sysadmin/tenants/view?id=' . $tenantId . '&error=not_found'));
                exit;
            }
            
            // Get director for this tenant
            $db = db();
            $directorStmt = $db->prepare('
                SELECT id, name, email 
                FROM users 
                WHERE tenant_id = :tenant_id AND role_key = "director" 
                LIMIT 1
            ');
            $directorStmt->execute(['tenant_id' => $tenantId]);
            $director = $directorStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$director) {
                header('Location: ' . base_url('sysadmin/tenants/view?id=' . $tenantId . '&error=no_director'));
                exit;
            }
            
            // Create payment record (optional - can be marked as free/manual)
            $paymentRepo = new \App\Repositories\LicensePaymentRepository();
            $paymentId = $paymentRepo->create([
                'tenant_id' => $tenantId,
                'package_id' => $packageId,
                'amount' => $package['price'],
                'currency' => $package['currency'],
                'payment_method' => 'manual',
                'payment_status' => 'completed',
                'payment_reference' => 'MANUAL-' . time(),
            ]);
            
            // Generate license with package duration
            $licenseKey = LicenseGenerator::generate();
            $installationId = $this->licenseRepo->getInstallationId();
            $signedToken = LicenseGenerator::signToken($licenseKey, $installationId, $director['email']);
            
            // Calculate expiration based on package duration
            $expiresAt = new \DateTime();
            $expiresAt->modify('+' . $package['duration_months'] . ' months');
            
            // Activate license
            $licenseId = $this->licenseRepo->activate(
                $installationId,
                $director['email'],
                (int)$director['id'],
                $licenseKey,
                $signedToken,
                $expiresAt,
                $packageId,
                $paymentId
            );
            
            // Log action
            $this->sysAdminRepo->logAction(
                $admin['id'],
                'assign_package',
                'tenant',
                $tenantId,
                [
                    'tenant_name' => $tenant['name'],
                    'package_name' => $package['name'],
                    'package_id' => $packageId,
                    'license_id' => $licenseId,
                    'payment_id' => $paymentId,
                ]
            );
            
            header('Location: ' . base_url('sysadmin/tenants/view?id=' . $tenantId . '&success=package_assigned'));
            exit;
        }
        
        // GET request - show package selection
        $tenantId = (int)$request->input('tenant_id');
        if (!$tenantId) {
            header('Location: ' . base_url('sysadmin/tenants'));
            exit;
        }
        
        $tenant = $this->tenantRepo->findById($tenantId);
        if (!$tenant) {
            header('Location: ' . base_url('sysadmin/tenants?error=not_found'));
            exit;
        }
        
        $packages = $this->packageRepo->active();
        
        $this->view('sysadmin/assign-package', [
            'pageTitle' => 'Assign License Package',
            'admin' => $admin,
            'tenant' => $tenant,
            'packages' => $packages,
        ]);
    }
    
    public function generateLicense(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        $tenantId = (int)$request->input('tenant_id');
        
        if (!$tenantId) {
            header('Location: ' . base_url('sysadmin/tenants?error=tenant_required'));
            exit;
        }
        
        $tenant = $this->tenantRepo->findById($tenantId);
        if (!$tenant) {
            header('Location: ' . base_url('sysadmin/tenants?error=tenant_not_found'));
            exit;
        }
        
        // Get director for this tenant
        $db = db();
        $directorStmt = $db->prepare('
            SELECT id, name, email 
            FROM users 
            WHERE tenant_id = :tenant_id AND role_key = "director" 
            LIMIT 1
        ');
        $directorStmt->execute(['tenant_id' => $tenantId]);
        $director = $directorStmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$director) {
            header('Location: ' . base_url('sysadmin/tenants/view?id=' . $tenantId . '&error=no_director'));
            exit;
        }
        
        // Generate license
        $licenseKey = LicenseGenerator::generate();
        $installationId = $this->licenseRepo->getInstallationId();
        $signedToken = LicenseGenerator::signToken($licenseKey, $installationId, $director['email']);
        
        // Calculate expiration (1 year from now, optional)
        $expiresAt = new \DateTime();
        $expiresAt->modify('+1 year');
        
        // Activate license
        $licenseId = $this->licenseRepo->activate(
            $installationId,
            $director['email'],
            (int)$director['id'],
            $licenseKey,
            $signedToken,
            $expiresAt
        );
        
        // Log action
        $this->sysAdminRepo->logAction(
            $admin['id'],
            'generate_license',
            'license',
            $licenseId,
            [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenant['name'],
                'director_email' => $director['email'],
                'license_key' => $licenseKey,
            ]
        );
        
        // Send email to director
        $this->sendLicenseEmail($director, $tenant, $licenseKey, $signedToken);
        
        header('Location: ' . base_url('sysadmin/licenses?success=license_generated&tenant_id=' . $tenantId));
        exit;
    }
    
    public function sendLicense(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        $licenseId = (int)$request->input('license_id');
        
        if (!$licenseId) {
            header('Location: ' . base_url('sysadmin/licenses?error=license_required'));
            exit;
        }
        
        $db = db();
        $stmt = $db->prepare('
            SELECT la.*, u.name AS director_name, u.email AS director_email, t.name AS tenant_name, t.id AS tenant_id
            FROM license_activations la
            LEFT JOIN users u ON u.id = la.director_user_id
            LEFT JOIN tenants t ON t.id = u.tenant_id
            WHERE la.id = :license_id
        ');
        $stmt->execute(['license_id' => $licenseId]);
        $license = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$license) {
            header('Location: ' . base_url('sysadmin/licenses?error=license_not_found'));
            exit;
        }
        
        $tenant = $this->tenantRepo->findById($license['tenant_id']);
        $director = [
            'name' => $license['director_name'],
            'email' => $license['director_email'],
        ];
        
        // Send email
        $this->sendLicenseEmail($director, $tenant, $license['license_key'], $license['signed_token']);
        
        // Log action
        $this->sysAdminRepo->logAction(
            $admin['id'],
            'resend_license',
            'license',
            $licenseId,
            [
                'tenant_id' => $license['tenant_id'],
                'director_email' => $license['director_email'],
            ]
        );
        
        header('Location: ' . base_url('sysadmin/licenses?success=license_sent&license_id=' . $licenseId));
        exit;
    }
    
    protected function sendLicenseEmail(array $director, array $tenant, string $licenseKey, string $signedToken): void
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
                    <p>Dear {$director['name']},</p>
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
                $director['email'],
                $subject,
                $body,
                $director['name']
            );
        } catch (\Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to send license email: " . $e->getMessage());
        }
    }
    
    public function logs(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        $page = (int)($request->input('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $db = db();
        $stmt = $db->prepare('
            SELECT sal.*, sa.username, sa.email
            FROM system_audit_logs sal
            INNER JOIN system_admins sa ON sa.id = sal.system_admin_id
            ORDER BY sal.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $db->query('SELECT COUNT(*) FROM system_audit_logs');
        $totalLogs = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalLogs / $limit);

        $this->view('sysadmin/logs', [
            'pageTitle' => 'System Audit Logs',
            'admin' => $admin,
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ]);
    }
    
    public function health(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        $health = $this->getSystemHealth();

        $this->view('sysadmin/health', [
            'pageTitle' => 'System Health',
            'admin' => $admin,
            'health' => $health,
        ]);
    }
    
    public function settings(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST') {
            $action = $request->input('action');
            
            if ($action === 'update_username') {
                $newUsername = trim($request->input('username'));
                if ($newUsername && $newUsername !== $admin['username']) {
                    // Check if username already exists
                    $existing = $this->sysAdminRepo->findByUsername($newUsername);
                    if ($existing && $existing['id'] !== $admin['id']) {
                        header('Location: ' . base_url('sysadmin/settings?error=username_exists'));
                        return;
                    }
                    
                    $this->sysAdminRepo->updateUsername($admin['id'], $newUsername);
                    $this->sysAdminRepo->logAction($admin['id'], 'update_username', 'system_admin', $admin['id']);
                    header('Location: ' . base_url('sysadmin/settings?success=username_updated'));
                    return;
                }
            } elseif ($action === 'update_password') {
                $currentPassword = $request->input('current_password');
                $newPassword = $request->input('new_password');
                $confirmPassword = $request->input('confirm_password');
                
                if (!$this->sysAdminRepo->verifyPassword($currentPassword, $admin['password_hash'])) {
                    header('Location: ' . base_url('sysadmin/settings?error=invalid_current_password'));
                    return;
                }
                
                if ($newPassword !== $confirmPassword) {
                    header('Location: ' . base_url('sysadmin/settings?error=password_mismatch'));
                    return;
                }
                
                if (strlen($newPassword) < 8) {
                    header('Location: ' . base_url('sysadmin/settings?error=password_too_short'));
                    return;
                }
                
                $this->sysAdminRepo->updatePassword($admin['id'], $newPassword);
                $this->sysAdminRepo->logAction($admin['id'], 'update_password', 'system_admin', $admin['id']);
                header('Location: ' . base_url('sysadmin/settings?success=password_updated'));
                return;
            } elseif ($action === 'enable_2fa') {
                $code = $request->input('verification_code');
                $secret = $request->input('two_factor_secret');
                
                if (!$secret || !$code) {
                    header('Location: ' . base_url('sysadmin/settings?error=2fa_setup_failed'));
                    return;
                }
                
                $twoFactorAuth = new \App\Services\TwoFactorAuth();
                if (!$twoFactorAuth::verify($secret, $code)) {
                    header('Location: ' . base_url('sysadmin/settings?error=invalid_2fa_code'));
                    return;
                }
                
                $backupCodes = \App\Services\TwoFactorAuth::generateBackupCodes();
                $this->sysAdminRepo->enable2FA($admin['id'], $secret, $backupCodes);
                $this->sysAdminRepo->logAction($admin['id'], 'enable_2fa', 'system_admin', $admin['id']);
                
                // Store backup codes in session temporarily to show to user
                $_SESSION['2fa_backup_codes'] = $backupCodes;
                header('Location: ' . base_url('sysadmin/settings?success=2fa_enabled'));
                return;
            } elseif ($action === 'disable_2fa') {
                $password = $request->input('password');
                if (!$this->sysAdminRepo->verifyPassword($password, $admin['password_hash'])) {
                    header('Location: ' . base_url('sysadmin/settings?error=invalid_password'));
                    return;
                }
                
                $this->sysAdminRepo->disable2FA($admin['id']);
                $this->sysAdminRepo->logAction($admin['id'], 'disable_2fa', 'system_admin', $admin['id']);
                header('Location: ' . base_url('sysadmin/settings?success=2fa_disabled'));
                return;
            } else {
                // Handle general settings update
                $this->sysAdminRepo->logAction(
                    $admin['id'],
                    'update_settings',
                    'system',
                    null,
                    ['settings' => $request->all()]
                );
                
                header('Location: ' . base_url('sysadmin/settings?success=1'));
                return;
            }
        }

        $this->view('sysadmin/settings', [
            'pageTitle' => 'System Settings',
            'admin' => $admin,
        ]);
    }
    
    public function setup2FA(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST') {
            $secret = \App\Services\TwoFactorAuth::generateSecret();
            $_SESSION['2fa_setup_secret'] = $secret;
            header('Location: ' . base_url('sysadmin/settings?setup_2fa=1'));
            return;
        }
        
        header('Location: ' . base_url('sysadmin/settings'));
    }
    
    public function analytics(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        // Get analytics data
        $db = db();
        
        // Total users across all tenants
        $userStmt = $db->query('SELECT COUNT(*) FROM users WHERE tenant_id IS NOT NULL');
        $totalUsers = (int)$userStmt->fetchColumn();
        
        // Total bookings (last 30 days)
        $bookingStmt = $db->query('
            SELECT COUNT(*) 
            FROM reservations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');
        $totalBookings = (int)$bookingStmt->fetchColumn();
        
        // Active licenses
        $licenseStmt = $db->query('SELECT COUNT(*) FROM license_activations WHERE status = "active"');
        $activeLicenses = (int)$licenseStmt->fetchColumn();

        $this->view('sysadmin/analytics', [
            'pageTitle' => 'Analytics & Reports',
            'admin' => $admin,
            'stats' => [
                'total_users' => $totalUsers,
                'total_bookings_30d' => $totalBookings,
                'active_licenses' => $activeLicenses,
            ],
        ]);
    }

    protected function getRecentActions(int $limit = 10): array
    {
        $db = db();
        $stmt = $db->prepare('
            SELECT sal.*, sa.username
            FROM system_audit_logs sal
            INNER JOIN system_admins sa ON sa.id = sal.system_admin_id
            ORDER BY sal.created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getSystemHealth(): array
    {
        $db = db();
        
        // Check database connectivity
        $dbHealthy = true;
        try {
            $db->query('SELECT 1');
        } catch (\Exception $e) {
            $dbHealthy = false;
        }
        
        // Check disk space
        $diskFree = disk_free_space(BASE_PATH);
        $diskTotal = disk_total_space(BASE_PATH);
        $diskUsagePercent = $diskTotal > 0 ? (1 - ($diskFree / $diskTotal)) * 100 : 0;
        
        // Check PHP version
        $phpVersion = PHP_VERSION;
        
        // Check memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'database' => [
                'status' => $dbHealthy ? 'healthy' : 'error',
                'message' => $dbHealthy ? 'Connected' : 'Connection failed',
            ],
            'disk' => [
                'free' => $diskFree,
                'total' => $diskTotal,
                'usage_percent' => round($diskUsagePercent, 2),
                'status' => $diskUsagePercent > 90 ? 'warning' : ($diskUsagePercent > 95 ? 'error' : 'healthy'),
            ],
            'php' => [
                'version' => $phpVersion,
                'status' => version_compare($phpVersion, '8.0.0', '>=') ? 'healthy' : 'warning',
            ],
            'memory' => [
                'usage' => $memoryUsage,
                'limit' => $memoryLimit,
            ],
        ];
    }

    protected function calculateUptime(): string
    {
        // Simple calculation - in production, this would track actual uptime
        return '99.97%';
    }

    public function login(Request $request): void
    {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['system_admin_id'])) {
            header('Location: ' . base_url('sysadmin/dashboard'));
            return;
        }

        $error = null;
        $require2FA = false;
        $adminId = null;

        if ($request->method() === 'POST') {
            $username = $request->input('username');
            $password = $request->input('password');
            $twoFactorCode = $request->input('two_factor_code');
            
            // Check if we're in 2FA verification step
            if (isset($_SESSION['sysadmin_2fa_pending']) && $twoFactorCode) {
                $adminId = (int)$_SESSION['sysadmin_2fa_pending'];
                $admin = $this->sysAdminRepo->findById($adminId);
                
                if ($admin && !empty($admin['two_factor_enabled']) && !empty($admin['two_factor_secret'])) {
                    $twoFactorAuth = new \App\Services\TwoFactorAuth();
                    $twoFactorCode = trim($twoFactorCode);
                    
                    // Check backup codes first (8 characters)
                    $backupCodes = !empty($admin['two_factor_backup_codes']) 
                        ? json_decode($admin['two_factor_backup_codes'], true) 
                        : [];
                    $isBackupCode = false;
                    
                    if (strlen($twoFactorCode) === 8) {
                        $isBackupCode = $twoFactorAuth::verifyBackupCode($twoFactorCode, $backupCodes);
                        
                        // If backup code used, remove it
                        if ($isBackupCode) {
                            $backupCodes = array_values(array_filter($backupCodes, function($code) use ($twoFactorCode) {
                                return strtoupper(trim($code)) !== strtoupper($twoFactorCode);
                            }));
                            $this->sysAdminRepo->enable2FA($adminId, $admin['two_factor_secret'], $backupCodes);
                        }
                    }
                    
                    // Verify TOTP code (6 digits) or backup code
                    $isValid = $isBackupCode;
                    if (!$isValid && strlen($twoFactorCode) === 6 && ctype_digit($twoFactorCode)) {
                        $isValid = $twoFactorAuth::verify($admin['two_factor_secret'], $twoFactorCode);
                    }
                    
                    if ($isValid) {
                        // Successful 2FA verification
                        unset($_SESSION['sysadmin_2fa_pending']);
                        unset($_SESSION['sysadmin_2fa_username']);
                        
                        $this->sysAdminRepo->logAction(
                            $admin['id'],
                            'login',
                            'system_admin',
                            $admin['id'],
                            ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', '2fa_used' => true]
                        );

                        $_SESSION['system_admin_id'] = $admin['id'];
                        $this->sysAdminRepo->updateLastActivity($admin['id']);
                        
                        header('Location: ' . base_url('sysadmin/dashboard'));
                        return;
                    } else {
                        $error = 'Invalid 2FA code. Please try again.';
                        $require2FA = true;
                        $adminId = $admin['id'];
                    }
                } else {
                    // 2FA was disabled between steps
                    unset($_SESSION['sysadmin_2fa_pending']);
                    unset($_SESSION['sysadmin_2fa_username']);
                    $error = '2FA verification failed. Please try logging in again.';
                }
            } elseif ($username && $password) {
                // Initial login attempt
                $admin = $this->sysAdminRepo->findByUsername($username);
                
                if ($admin && $this->sysAdminRepo->verifyPassword($password, $admin['password_hash'])) {
                    // Check if 2FA is enabled
                    if (!empty($admin['two_factor_enabled']) && !empty($admin['two_factor_secret'])) {
                        // Require 2FA verification - store credentials temporarily
                        $_SESSION['sysadmin_2fa_pending'] = $admin['id'];
                        $_SESSION['sysadmin_2fa_username'] = $username;
                        $require2FA = true;
                        $adminId = $admin['id'];
                    } else {
                        // No 2FA, proceed with login
                        $this->sysAdminRepo->logAction(
                            $admin['id'],
                            'login',
                            'system_admin',
                            $admin['id'],
                            ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                        );

                        $_SESSION['system_admin_id'] = $admin['id'];
                        $this->sysAdminRepo->updateLastActivity($admin['id']);
                        
                        header('Location: ' . base_url('sysadmin/dashboard'));
                        return;
                    }
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Please enter your username and password';
            }
        }

        $this->view('sysadmin/login', [
            'error' => $error,
            'require2FA' => $require2FA,
            'adminId' => $adminId,
            'username' => $_SESSION['sysadmin_2fa_username'] ?? ($_POST['username'] ?? ''),
        ]);
    }

    public function logout(): void
    {
        if (isset($_SESSION['system_admin_id'])) {
            $adminId = (int)$_SESSION['system_admin_id'];
            $this->sysAdminRepo->logAction(
                $adminId,
                'logout',
                'system_admin',
                $adminId,
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
            );
        }
        
        // Clear all system admin session variables
        unset($_SESSION['system_admin_id']);
        
        // Also clear any tenant session if present (shouldn't be, but be thorough)
        if (isset($_SESSION['user_id'])) {
            \App\Support\Auth::logout();
        }
        
        // Destroy session completely for security
        session_destroy();
        session_start();
        
        header('Location: ' . base_url('sysadmin/login?logged_out=1'));
        exit;
    }

    public function packages(Request $request): void
    {
        $admin = $this->sysAdminRepo->findById((int)$_SESSION['system_admin_id']);
        
        if ($request->method() === 'POST') {
            $action = $request->input('action');
            
            if ($action === 'create') {
                $featuresText = $request->input('features', '');
                $features = [];
                if ($featuresText) {
                    $features = array_filter(array_map('trim', explode("\n", $featuresText)));
                }
                
                $packageId = $this->packageRepo->create([
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'price' => (float)$request->input('price'),
                    'currency' => $request->input('currency', 'USD'),
                    'duration_months' => (int)$request->input('duration_months', 12),
                    'features' => $features,
                    'is_active' => $request->input('is_active', 1) ? 1 : 0,
                    'sort_order' => (int)$request->input('sort_order', 0),
                ]);
                
                $this->sysAdminRepo->logAction($admin['id'], 'create_package', 'package', $packageId);
                header('Location: ' . base_url('sysadmin/packages?success=created'));
                return;
            } elseif ($action === 'update') {
                $packageId = (int)$request->input('id');
                $featuresText = $request->input('features', '');
                $features = [];
                if ($featuresText) {
                    $features = array_filter(array_map('trim', explode("\n", $featuresText)));
                }
                
                $this->packageRepo->update($packageId, [
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'price' => (float)$request->input('price'),
                    'currency' => $request->input('currency', 'USD'),
                    'duration_months' => (int)$request->input('duration_months', 12),
                    'features' => $features,
                    'is_active' => $request->input('is_active', 1) ? 1 : 0,
                    'sort_order' => (int)$request->input('sort_order', 0),
                ]);
                
                $this->sysAdminRepo->logAction($admin['id'], 'update_package', 'package', $packageId);
                header('Location: ' . base_url('sysadmin/packages?success=updated'));
                return;
            } elseif ($action === 'delete') {
                $packageId = (int)$request->input('id');
                $this->packageRepo->delete($packageId);
                $this->sysAdminRepo->logAction($admin['id'], 'delete_package', 'package', $packageId);
                header('Location: ' . base_url('sysadmin/packages?success=deleted'));
                return;
            }
        }
        
        $packages = $this->packageRepo->all();
        
        $this->view('sysadmin/packages', [
            'pageTitle' => 'License Packages',
            'admin' => $admin,
            'packages' => $packages,
        ]);
    }
}

