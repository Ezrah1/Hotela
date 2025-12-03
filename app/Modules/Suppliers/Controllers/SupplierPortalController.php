<?php

namespace App\Modules\Suppliers\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SupplierRepository;
use App\Repositories\SupplierLoginCodeRepository;
use App\Support\SupplierPortal;
use App\Services\Email\EmailService;

class SupplierPortalController extends Controller
{
    protected SupplierRepository $suppliers;
    protected SupplierLoginCodeRepository $loginCodes;
    protected EmailService $emailService;

    public function __construct()
    {
        $this->suppliers = new SupplierRepository();
        $this->loginCodes = new SupplierLoginCodeRepository();
        $this->emailService = new EmailService();
    }

    public function showLogin(Request $request): void
    {
        if (SupplierPortal::check()) {
            header('Location: ' . base_url('supplier/portal'));
            return;
        }

        $this->view('supplier/login', [
            'redirect' => $request->input('redirect', base_url('supplier/portal')),
            'pageTitle' => 'Supplier Portal Login | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function authenticate(Request $request): void
    {
        $loginMethod = $request->input('login_method', 'password'); // 'password' or 'code'
        $redirect = $request->input('redirect', base_url('supplier/portal'));

        if ($loginMethod === 'code') {
            // Login using email code
            $email = trim((string)$request->input('email'));
            $code = trim((string)$request->input('code'));

            if ($email === '' || $code === '') {
                header('Location: ' . base_url('supplier/login?redirect=' . urlencode($redirect) . '&error=missing&method=code'));
                return;
            }

            // Verify the code
            $codeRecord = $this->loginCodes->findValidCode($email, $code);
            if (!$codeRecord) {
                header('Location: ' . base_url('supplier/login?redirect=' . urlencode($redirect) . '&error=invalid_code&method=code'));
                return;
            }

            // Mark code as used
            $this->loginCodes->markAsUsed((int)$codeRecord['id']);

            // Login supplier
            SupplierPortal::login([
                'supplier_id' => (int)$codeRecord['supplier_id'],
                'supplier_name' => $codeRecord['supplier_name'] ?? '',
                'supplier_email' => $codeRecord['supplier_email'] ?? $email,
                'supplier_phone' => $codeRecord['supplier_phone'] ?? null,
                'identifier' => $email,
                'identifier_type' => 'email',
            ]);

            header('Location: ' . $redirect);
            return;
        } else {
            // Login using password
            $email = trim((string)$request->input('email'));
            $password = trim((string)$request->input('password'));

            if ($email === '' || $password === '') {
                header('Location: ' . base_url('supplier/login?redirect=' . urlencode($redirect) . '&error=missing&method=password'));
                return;
            }

            // Find supplier by email
            $supplier = $this->suppliers->findByEmail($email);
            if (!$supplier || !$supplier['portal_enabled']) {
                header('Location: ' . base_url('supplier/login?redirect=' . urlencode($redirect) . '&error=invalid_credentials&method=password'));
                return;
            }

            // Verify password
            if (empty($supplier['password_hash']) || !password_verify($password, $supplier['password_hash'])) {
                header('Location: ' . base_url('supplier/login?redirect=' . urlencode($redirect) . '&error=invalid_credentials&method=password'));
                return;
            }

            // Login supplier
            SupplierPortal::login([
                'supplier_id' => (int)$supplier['id'],
                'supplier_name' => $supplier['name'] ?? '',
                'supplier_email' => $supplier['email'] ?? $email,
                'supplier_phone' => $supplier['phone'] ?? null,
                'identifier' => $email,
                'identifier_type' => 'email',
            ]);

            header('Location: ' . $redirect);
            return;
        }
    }

    public function requestCode(Request $request): void
    {
        header('Content-Type: application/json');

        $email = trim((string)$request->input('email'));
        if ($email === '') {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            return;
        }

        // Rate limiting
        if ($this->loginCodes->hasRecentRequest($email, 2)) {
            echo json_encode(['success' => false, 'message' => 'Please wait before requesting another code']);
            return;
        }

        // Find supplier by email
        $supplier = $this->suppliers->findByEmail($email);
        if (!$supplier || !$supplier['portal_enabled']) {
            // Don't reveal if supplier exists or not for security
            echo json_encode(['success' => true, 'message' => 'If a supplier account exists with this email, a login code has been sent.']);
            return;
        }

        // Generate 6-digit code
        $code = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create login code
        $this->loginCodes->create((int)$supplier['id'], $email, $code, 15);

        // Send email with code
        try {
            $this->emailService->sendSupplierLoginCode(
                $email,
                $supplier['name'] ?? 'Supplier',
                $code
            );
        } catch (\Exception $e) {
            error_log('Failed to send supplier login code email: ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Login code sent to your email']);
    }

    public function logout(Request $request): void
    {
        SupplierPortal::logout();
        header('Location: ' . base_url('supplier/login'));
    }

    public function dashboard(Request $request): void
    {
        SupplierPortal::requireLogin();

        $supplierId = SupplierPortal::supplierId();
        if (!$supplierId) {
            header('Location: ' . base_url('supplier/login'));
            return;
        }

        $supplier = $this->suppliers->find($supplierId);
        if (!$supplier) {
            SupplierPortal::logout();
            header('Location: ' . base_url('supplier/login?error=account_not_found'));
            return;
        }

        // Get purchase orders for this supplier
        $purchaseOrders = $this->suppliers->getPurchaseOrders($supplierId);
        
        // Get performance history
        $performanceHistory = $this->suppliers->getPerformanceHistory($supplierId, 10);

        // Calculate statistics
        $stats = [
            'total_orders' => count($purchaseOrders),
            'pending_orders' => count(array_filter($purchaseOrders, fn($po) => in_array($po['status'], ['sent', 'in_transit']))),
            'completed_orders' => count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'received')),
            'unpaid_invoices' => count(array_filter($purchaseOrders, fn($po) => ($po['payment_status'] ?? 'unpaid') === 'unpaid')),
            'total_amount' => array_sum(array_column($purchaseOrders, 'total_amount')),
        ];

        $this->view('supplier/dashboard', [
            'supplier' => $supplier,
            'purchaseOrders' => $purchaseOrders,
            'performanceHistory' => $performanceHistory,
            'stats' => $stats,
        ]);
    }

    public function purchaseOrders(Request $request): void
    {
        SupplierPortal::requireLogin();

        $supplierId = SupplierPortal::supplierId();
        if (!$supplierId) {
            header('Location: ' . base_url('supplier/login'));
            return;
        }

        $purchaseOrders = $this->suppliers->getPurchaseOrders($supplierId);
        $status = $request->input('status');

        if ($status) {
            $purchaseOrders = array_filter($purchaseOrders, fn($po) => $po['status'] === $status);
        }

        $this->view('supplier/purchase-orders', [
            'purchaseOrders' => $purchaseOrders,
            'status' => $status,
        ]);
    }

    public function showPurchaseOrder(Request $request): void
    {
        SupplierPortal::requireLogin();

        $supplierId = SupplierPortal::supplierId();
        $poId = (int)$request->input('id');

        if (!$supplierId || !$poId) {
            header('Location: ' . base_url('supplier/purchase-orders'));
            return;
        }

        // Get purchase order and verify it belongs to this supplier
        $purchaseOrders = $this->suppliers->getPurchaseOrders($supplierId);
        $purchaseOrder = null;
        foreach ($purchaseOrders as $po) {
            if ((int)$po['id'] === $poId) {
                $purchaseOrder = $po;
                break;
            }
        }

        if (!$purchaseOrder) {
            header('Location: ' . base_url('supplier/purchase-orders?error=not_found'));
            return;
        }

        // Get purchase order items
        $requisitionRepo = new \App\Repositories\RequisitionRepository();
        $items = $requisitionRepo->purchaseOrderItems($poId);

        $this->view('supplier/purchase-order-detail', [
            'purchaseOrder' => $purchaseOrder,
            'items' => $items,
        ]);
    }
}

