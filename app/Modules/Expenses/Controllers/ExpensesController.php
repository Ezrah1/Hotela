<?php

namespace App\Modules\Expenses\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseCategoryRepository;
use App\Repositories\SupplierRepository;
use App\Support\Auth;

class ExpensesController extends Controller
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
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');
        $department = $request->input('department', '');
        $status = $request->input('status', '');
        $supplierId = $request->input('supplier_id') ? (int)$request->input('supplier_id') : null;
        $categoryId = $request->input('category_id') ? (int)$request->input('category_id') : null;

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        $allExpenses = $this->expenses->all($start, $end, $department ?: null, $status ?: null, $supplierId, $categoryId);
        
        // Filter out bills (expenses with bill_reference and supplier_id are bills)
        $expenses = array_filter($allExpenses, function($expense) {
            return empty($expense['bill_reference']) || empty($expense['supplier_id']);
        });
        
        $summary = $this->expenses->getSummary($start, $end, $department ?: null);
        $byDepartment = $this->expenses->getByDepartment($start, $end);
        $byCategory = $this->expenses->getByCategory($start, $end);
        $bySupplier = $this->expenses->getBySupplier($start, $end);

        $this->view('dashboard/expenses/index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'byDepartment' => $byDepartment,
            'byCategory' => $byCategory,
            'bySupplier' => $bySupplier,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'department' => $department,
                'status' => $status,
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
            ],
            'categories' => $this->categories->all(),
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager']);

        if ($request->method() === 'POST') {
            $this->store($request);
            return;
        }

        $this->view('dashboard/expenses/create', [
            'categories' => $this->categories->all(),
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager']);

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/expenses/create?error=' . urlencode('User not authenticated')));
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
            header('Location: ' . base_url('dashboard/expenses/create?error=' . urlencode('Description and amount are required')));
            return;
        }

        try {
            $id = $this->expenses->create($data);
            
            // If expense has a petty cash category, deduct from petty cash
            if (!empty($data['category_id'])) {
                $category = $this->categories->find($data['category_id']);
                if ($category && !empty($category['is_petty_cash'])) {
                    $pettyCash = new \App\Repositories\PettyCashRepository();
                    if ($pettyCash->canWithdraw($data['amount'])) {
                        $pettyCash->addTransaction([
                            'transaction_type' => 'expense',
                            'amount' => $data['amount'],
                            'description' => 'Expense: ' . $data['description'],
                            'expense_id' => $id,
                            'processed_by' => $user['id'],
                        ]);
                    }
                }
            }
            
            header('Location: ' . base_url('dashboard/expenses?success=' . urlencode('Expense created successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/expenses/create?error=' . urlencode('Failed to create expense: ' . $e->getMessage())));
        }
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Invalid expense ID')));
            return;
        }

        $expense = $this->expenses->find($id);
        if (!$expense) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Expense not found')));
            return;
        }

        if ($request->method() === 'POST') {
            $this->update($request);
            return;
        }

        $this->view('dashboard/expenses/edit', [
            'expense' => $expense,
            'categories' => $this->categories->all(),
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Invalid expense ID')));
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
            header('Location: ' . base_url('dashboard/expenses/edit?id=' . $id . '&error=' . urlencode('Description and amount are required')));
            return;
        }

        try {
            $existingExpense = $this->expenses->find($id);
            $oldCategoryId = $existingExpense['category_id'] ?? null;
            $oldAmount = (float)($existingExpense['amount'] ?? 0);
            
            $this->expenses->update($id, $data);
            
            // Handle petty cash category changes
            $pettyCash = new \App\Repositories\PettyCashRepository();
            $newCategory = !empty($data['category_id']) ? $this->categories->find($data['category_id']) : null;
            $oldCategory = $oldCategoryId ? $this->categories->find($oldCategoryId) : null;
            
            $isOldPettyCash = $oldCategory && !empty($oldCategory['is_petty_cash']);
            $isNewPettyCash = $newCategory && !empty($newCategory['is_petty_cash']);
            
            // If old category was petty cash, reverse the transaction
            if ($isOldPettyCash && !$isNewPettyCash) {
                // Refund to petty cash (add back)
                $pettyCash->addTransaction([
                    'transaction_type' => 'deposit',
                    'amount' => $oldAmount,
                    'description' => 'Refund: Expense #' . $id . ' category changed',
                    'processed_by' => \App\Support\Auth::user()['id'] ?? null,
                ]);
            }
            
            // If new category is petty cash, deduct from petty cash
            if ($isNewPettyCash) {
                $newAmount = $data['amount'];
                if ($isOldPettyCash) {
                    // Adjust the difference
                    $difference = $newAmount - $oldAmount;
                    if ($difference > 0) {
                        // Additional deduction needed
                        if ($pettyCash->canWithdraw($difference)) {
                            $pettyCash->addTransaction([
                                'transaction_type' => 'expense',
                                'amount' => $difference,
                                'description' => 'Expense adjustment: ' . $data['description'],
                                'expense_id' => $id,
                                'processed_by' => \App\Support\Auth::user()['id'] ?? null,
                            ]);
                        }
                    } elseif ($difference < 0) {
                        // Refund the difference
                        $pettyCash->addTransaction([
                            'transaction_type' => 'deposit',
                            'amount' => abs($difference),
                            'description' => 'Expense adjustment refund: ' . $data['description'],
                            'processed_by' => \App\Support\Auth::user()['id'] ?? null,
                        ]);
                    }
                } else {
                    // New petty cash expense
                    if ($pettyCash->canWithdraw($newAmount)) {
                        $pettyCash->addTransaction([
                            'transaction_type' => 'expense',
                            'amount' => $newAmount,
                            'description' => 'Expense: ' . $data['description'],
                            'expense_id' => $id,
                            'processed_by' => \App\Support\Auth::user()['id'] ?? null,
                        ]);
                    }
                }
            }
            
            header('Location: ' . base_url('dashboard/expenses?success=' . urlencode('Expense updated successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/expenses/edit?id=' . $id . '&error=' . urlencode('Failed to update expense: ' . $e->getMessage())));
        }
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Invalid expense ID')));
            return;
        }

        $expense = $this->expenses->find($id);
        if (!$expense) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Expense not found')));
            return;
        }

        $attachments = $this->expenses->getAttachments($id);

        $this->view('dashboard/expenses/show', [
            'expense' => $expense,
            'attachments' => $attachments,
        ]);
    }

    public function approve(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Invalid expense ID')));
            return;
        }

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('User not authenticated')));
            return;
        }

        try {
            $this->expenses->approve($id, $user['id']);
            header('Location: ' . base_url('dashboard/expenses?success=' . urlencode('Expense approved successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Failed to approve expense')));
        }
    }

    public function markPaid(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $id = (int)$request->input('id');
        if (!$id) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Invalid expense ID')));
            return;
        }

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('User not authenticated')));
            return;
        }

        try {
            $this->expenses->markPaid($id, $user['id']);
            
            // Update supplier balance if expense is linked to a supplier
            $expense = $this->expenses->find($id);
            if ($expense && !empty($expense['supplier_id'])) {
                $supplier = $this->suppliers->find($expense['supplier_id']);
                if ($supplier) {
                    $newBalance = (float)($supplier['current_balance'] ?? 0) + (float)$expense['amount'];
                    $this->suppliers->update($expense['supplier_id'], [
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
            
            header('Location: ' . base_url('dashboard/expenses?success=' . urlencode('Expense marked as paid')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/expenses?error=' . urlencode('Failed to mark expense as paid')));
        }
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

