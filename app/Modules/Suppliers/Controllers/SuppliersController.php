<?php

namespace App\Modules\Suppliers\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SupplierRepository;
use App\Support\Auth;

class SuppliersController extends Controller
{
    protected SupplierRepository $suppliers;

    public function __construct()
    {
        $this->suppliers = new SupplierRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $search = $request->input('search', '');
        $category = $request->input('category', '');
        $status = $request->input('status', '');
        $group = $request->input('group', '');
        
        $suppliers = $this->suppliers->all(
            $search ?: null,
            $category ?: null,
            $status ?: null,
            $group ?: null
        );

        $groups = $this->suppliers->getGroups();

        $this->view('dashboard/suppliers/index', [
            'suppliers' => $suppliers,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'group' => $group,
            'groups' => $groups,
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        if ($request->method() === 'POST') {
            $this->store($request);
            return;
        }

        $groups = $this->suppliers->getGroups();
        
        $this->view('dashboard/suppliers/create', [
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $data = [
            'name' => trim($request->input('name', '')),
            'contact_person' => trim($request->input('contact_person', '')),
            'email' => trim($request->input('email', '')),
            'phone' => trim($request->input('phone', '')),
            'address' => trim($request->input('address', '')),
            'city' => trim($request->input('city', '')),
            'country' => trim($request->input('country', '')),
            'tax_id' => trim($request->input('tax_id', '')),
            'payment_terms' => trim($request->input('payment_terms', '')),
            'notes' => trim($request->input('notes', '')),
            'status' => $request->input('status', 'active'),
            'category' => $request->input('category', 'product_supplier'),
            'supplier_group' => trim($request->input('supplier_group', '')) ?: null,
            'bank_name' => trim($request->input('bank_name', '')),
            'bank_account_number' => trim($request->input('bank_account_number', '')),
            'bank_branch' => trim($request->input('bank_branch', '')),
            'bank_swift_code' => trim($request->input('bank_swift_code', '')),
            'payment_methods' => trim($request->input('payment_methods', '')),
            'credit_limit' => (float)$request->input('credit_limit', 0),
            'current_balance' => 0, // Start with zero balance
        ];

        if (empty($data['name'])) {
            header('Location: ' . base_url('staff/dashboard/suppliers/create?error=' . urlencode('Supplier name is required')));
            return;
        }

        try {
            $id = $this->suppliers->create($data);
            header('Location: ' . base_url('staff/dashboard/suppliers?success=' . urlencode('Supplier created successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/suppliers/create?error=' . urlencode('Failed to create supplier')));
        }
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Invalid supplier ID')));
            return;
        }

        $supplier = $this->suppliers->find($id);
        if (!$supplier) {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Supplier not found')));
            return;
        }

        if ($request->method() === 'POST') {
            $this->update($request);
            return;
        }

        $groups = $this->suppliers->getGroups();
        
        $this->view('dashboard/suppliers/edit', [
            'supplier' => $supplier,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/suppliers?error=' . urlencode('Invalid supplier ID')));
            return;
        }

        // Get existing supplier to preserve current_balance if not being updated
        $existingSupplier = $this->suppliers->find($id);
        $currentBalance = $existingSupplier ? (float)($existingSupplier['current_balance'] ?? 0) : 0;

        $data = [
            'name' => trim($request->input('name', '')),
            'contact_person' => trim($request->input('contact_person', '')),
            'email' => trim($request->input('email', '')),
            'phone' => trim($request->input('phone', '')),
            'address' => trim($request->input('address', '')),
            'city' => trim($request->input('city', '')),
            'country' => trim($request->input('country', '')),
            'tax_id' => trim($request->input('tax_id', '')),
            'payment_terms' => trim($request->input('payment_terms', '')),
            'notes' => trim($request->input('notes', '')),
            'status' => $request->input('status', 'active'),
            'category' => $request->input('category', 'product_supplier'),
            'supplier_group' => trim($request->input('supplier_group', '')) ?: null,
            'bank_name' => trim($request->input('bank_name', '')),
            'bank_account_number' => trim($request->input('bank_account_number', '')),
            'bank_branch' => trim($request->input('bank_branch', '')),
            'bank_swift_code' => trim($request->input('bank_swift_code', '')),
            'payment_methods' => trim($request->input('payment_methods', '')),
            'credit_limit' => (float)$request->input('credit_limit', 0),
            'current_balance' => $currentBalance, // Preserve existing balance
        ];

        if (empty($data['name'])) {
            header('Location: ' . base_url('staff/dashboard/suppliers/edit?id=' . $id . '&error=' . urlencode('Supplier name is required')));
            return;
        }

        try {
            $this->suppliers->update($id, $data);
            header('Location: ' . base_url('staff/dashboard/suppliers?success=' . urlencode('Supplier updated successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/suppliers/edit?id=' . $id . '&error=' . urlencode('Failed to update supplier')));
        }
    }

    public function delete(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Invalid supplier ID')));
            return;
        }

        $deleted = $this->suppliers->delete($id);
        if ($deleted) {
            header('Location: ' . base_url('staff/dashboard/suppliers?success=' . urlencode('Supplier deleted successfully')));
        } else {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Cannot delete supplier with purchase orders')));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Invalid supplier ID')));
            return;
        }

        $supplier = $this->suppliers->find($id);
        if (!$supplier) {
            header('Location: ' . base_url('staff/dashboard/suppliers?error=' . urlencode('Supplier not found')));
            return;
        }

        $purchaseOrders = $this->suppliers->getPurchaseOrders($id);
        $performanceHistory = $this->suppliers->getPerformanceHistory($id, 10);

        $this->view('dashboard/suppliers/show', [
            'supplier' => $supplier,
            'purchaseOrders' => $purchaseOrders,
            'performanceHistory' => $performanceHistory,
        ]);
    }
}

