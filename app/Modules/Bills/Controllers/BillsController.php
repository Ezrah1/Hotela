<?php

namespace App\Modules\Bills\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseCategoryRepository;
use App\Repositories\SupplierRepository;
use App\Support\Auth;

class BillsController extends Controller
{
    protected ExpenseRepository $expenses;
    protected ExpenseCategoryRepository $categories;
    protected SupplierRepository $suppliers;

    public function __construct()
    {
        $this->expenses = new ExpenseRepository();
        $this->categories = new ExpenseCategoryRepository();
        $this->suppliers = new SupplierRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');
        $department = $request->input('department', '');
        $status = $request->input('status', '');
        $supplierId = $request->input('supplier_id') ? (int)$request->input('supplier_id') : null;

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        // Get only bills (expenses with bill_reference or supplier_id)
        $bills = $this->getBills($start, $end, $department ?: null, $status ?: null, $supplierId);
        $summary = $this->getBillsSummary($bills);
        $bySupplier = $this->getBillsBySupplier($start, $end);

        $this->view('dashboard/bills/index', [
            'bills' => $bills,
            'summary' => $summary,
            'bySupplier' => $bySupplier,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'department' => $department,
                'status' => $status,
                'supplier_id' => $supplierId,
            ],
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        if ($request->method() === 'POST') {
            $this->store($request);
            return;
        }

        $this->view('dashboard/bills/create', [
            'categories' => $this->categories->all(),
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/bills/create?error=' . urlencode('User not authenticated')));
            return;
        }

        $data = [
            'category_id' => $request->input('category_id') ? (int)$request->input('category_id') : null,
            'supplier_id' => $request->input('supplier_id') ? (int)$request->input('supplier_id') : null,
            'department' => trim($request->input('department', '')),
            'description' => trim($request->input('description', '')),
            'amount' => (float)$request->input('amount', 0),
            'expense_date' => $this->sanitizeDate($request->input('expense_date')) ?? date('Y-m-d'),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
            'bill_reference' => trim($request->input('bill_reference', '')),
            'is_recurring' => $request->input('is_recurring') ? 1 : 0,
            'recurring_frequency' => $request->input('recurring_frequency') ?: null,
            'status' => $request->input('status', 'pending'),
            'notes' => trim($request->input('notes', '')),
            'created_by' => $user['id'],
        ];

        if (empty($data['description']) || $data['amount'] <= 0) {
            header('Location: ' . base_url('dashboard/bills/create?error=' . urlencode('Description and amount are required')));
            return;
        }

        if (empty($data['bill_reference'])) {
            header('Location: ' . base_url('dashboard/bills/create?error=' . urlencode('Bill reference is required')));
            return;
        }

        if (empty($data['supplier_id'])) {
            header('Location: ' . base_url('dashboard/bills/create?error=' . urlencode('Supplier is required for bills')));
            return;
        }

        try {
            $id = $this->expenses->create($data);
            header('Location: ' . base_url('dashboard/bills?success=' . urlencode('Bill created successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/bills/create?error=' . urlencode('Failed to create bill')));
        }
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Invalid bill ID')));
            return;
        }

        $bill = $this->expenses->find($id);
        if (!$bill || empty($bill['bill_reference'])) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Bill not found')));
            return;
        }

        if ($request->method() === 'POST') {
            $this->update($request);
            return;
        }

        $this->view('dashboard/bills/edit', [
            'bill' => $bill,
            'categories' => $this->categories->all(),
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Invalid bill ID')));
            return;
        }

        $data = [
            'category_id' => $request->input('category_id') ? (int)$request->input('category_id') : null,
            'supplier_id' => $request->input('supplier_id') ? (int)$request->input('supplier_id') : null,
            'department' => trim($request->input('department', '')),
            'description' => trim($request->input('description', '')),
            'amount' => (float)$request->input('amount', 0),
            'expense_date' => $this->sanitizeDate($request->input('expense_date')) ?? date('Y-m-d'),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
            'bill_reference' => trim($request->input('bill_reference', '')),
            'is_recurring' => $request->input('is_recurring') ? 1 : 0,
            'recurring_frequency' => $request->input('recurring_frequency') ?: null,
            'status' => $request->input('status', 'pending'),
            'notes' => trim($request->input('notes', '')),
        ];

        if (empty($data['description']) || $data['amount'] <= 0) {
            header('Location: ' . base_url('dashboard/bills/edit?id=' . $id . '&error=' . urlencode('Description and amount are required')));
            return;
        }

        if (empty($data['bill_reference'])) {
            header('Location: ' . base_url('dashboard/bills/edit?id=' . $id . '&error=' . urlencode('Bill reference is required')));
            return;
        }

        try {
            $this->expenses->update($id, $data);
            header('Location: ' . base_url('dashboard/bills?success=' . urlencode('Bill updated successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/bills/edit?id=' . $id . '&error=' . urlencode('Failed to update bill')));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Invalid bill ID')));
            return;
        }

        $bill = $this->expenses->find($id);
        if (!$bill || empty($bill['bill_reference'])) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Bill not found')));
            return;
        }

        $attachments = $this->expenses->getAttachments($id);

        $this->view('dashboard/bills/show', [
            'bill' => $bill,
            'attachments' => $attachments,
        ]);
    }

    public function approve(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Invalid bill ID')));
            return;
        }

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('User not authenticated')));
            return;
        }

        try {
            $this->expenses->approve($id, $user['id']);
            header('Location: ' . base_url('dashboard/bills?success=' . urlencode('Bill approved successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Failed to approve bill')));
        }
    }

    public function markPaid(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Invalid bill ID')));
            return;
        }

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('User not authenticated')));
            return;
        }

        try {
            $this->expenses->markPaid($id, $user['id']);
            
            // Update supplier balance
            $bill = $this->expenses->find($id);
            if ($bill && !empty($bill['supplier_id'])) {
                $supplier = $this->suppliers->find($bill['supplier_id']);
                if ($supplier) {
                    $newBalance = (float)($supplier['current_balance'] ?? 0) + (float)$bill['amount'];
                    $this->suppliers->update($bill['supplier_id'], [
                        'name' => $supplier['name'],
                        'contact_person' => $supplier['contact_person'] ?? '',
                        'email' => $supplier['email'] ?? '',
                        'phone' => $supplier['phone'] ?? '',
                        'address' => $supplier['address'] ?? '',
                        'city' => $supplier['city'] ?? '',
                        'country' => $supplier['country'] ?? '',
                        'tax_id' => $supplier['tax_id'] ?? '',
                        'payment_terms' => $supplier['payment_terms'] ?? '',
                        'notes' => $supplier['notes'] ?? '',
                        'status' => $supplier['status'] ?? 'active',
                        'bank_name' => $supplier['bank_name'] ?? '',
                        'bank_account_number' => $supplier['bank_account_number'] ?? '',
                        'bank_branch' => $supplier['bank_branch'] ?? '',
                        'bank_swift_code' => $supplier['bank_swift_code'] ?? '',
                        'payment_methods' => $supplier['payment_methods'] ?? '',
                        'credit_limit' => (float)($supplier['credit_limit'] ?? 0),
                        'current_balance' => $newBalance,
                    ]);
                }
            }
            
            header('Location: ' . base_url('dashboard/bills?success=' . urlencode('Bill marked as paid')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/bills?error=' . urlencode('Failed to mark bill as paid')));
        }
    }

    protected function getBills(?string $startDate = null, ?string $endDate = null, ?string $department = null, ?string $status = null, ?int $supplierId = null): array
    {
        $allExpenses = $this->expenses->all($startDate, $endDate, $department, $status, $supplierId, null);
        
        // Filter to only bills (must have bill_reference and supplier_id)
        return array_filter($allExpenses, function($expense) {
            return !empty($expense['bill_reference']) && !empty($expense['supplier_id']);
        });
    }

    protected function getBillsSummary(array $bills): array
    {
        $total = 0;
        $pending = 0;
        $approved = 0;
        $paid = 0;
        $count = count($bills);

        foreach ($bills as $bill) {
            $amount = (float)$bill['amount'];
            $total += $amount;
            
            switch ($bill['status']) {
                case 'pending':
                    $pending += $amount;
                    break;
                case 'approved':
                    $approved += $amount;
                    break;
                case 'paid':
                    $paid += $amount;
                    break;
            }
        }

        return [
            'total_amount' => $total,
            'total_count' => $count,
            'pending_amount' => $pending,
            'approved_amount' => $approved,
            'paid_amount' => $paid,
        ];
    }

    protected function getBillsBySupplier(?string $startDate = null, ?string $endDate = null): array
    {
        $bills = $this->getBills($startDate, $endDate);
        
        $bySupplier = [];
        foreach ($bills as $bill) {
            $supplierId = $bill['supplier_id'];
            $supplierName = $bill['supplier_name'];
            
            if (!isset($bySupplier[$supplierId])) {
                $bySupplier[$supplierId] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplierName,
                    'count' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                ];
            }
            
            $bySupplier[$supplierId]['count']++;
            $bySupplier[$supplierId]['total_amount'] += (float)$bill['amount'];
            
            if ($bill['status'] === 'paid') {
                $bySupplier[$supplierId]['paid_amount'] += (float)$bill['amount'];
            }
        }
        
        // Sort by total amount descending
        usort($bySupplier, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        
        return $bySupplier;
    }

    protected function sanitizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}

