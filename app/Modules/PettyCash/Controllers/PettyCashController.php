<?php

namespace App\Modules\PettyCash\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PettyCashRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;

class PettyCashController extends Controller
{
    protected PettyCashRepository $pettyCash;
    protected ExpenseRepository $expenses;
    protected UserRepository $users;

    public function __construct()
    {
        $this->pettyCash = new PettyCashRepository();
        $this->expenses = new ExpenseRepository();
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'operation_manager', 'cashier']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');
        $type = $request->input('type', '');

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        $account = $this->pettyCash->getAccount();
        $transactions = $this->pettyCash->getTransactions($start, $end, $type ?: null);
        $summary = $this->pettyCash->getSummary($start, $end);

        $this->view('dashboard/petty-cash/index', [
            'account' => $account,
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'type' => $type,
            ],
        ]);
    }

    public function deposit(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        if ($request->method() === 'POST') {
            $this->processDeposit($request);
            return;
        }

        $this->view('dashboard/petty-cash/deposit', [
            'account' => $this->pettyCash->getAccount(),
        ]);
    }

    public function processDeposit(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/petty-cash/deposit?error=' . urlencode('User not authenticated')));
            return;
        }

        $amount = (float)$request->input('amount', 0);
        $description = trim($request->input('description', ''));
        $notes = trim($request->input('notes', ''));

        if ($amount <= 0) {
            header('Location: ' . base_url('dashboard/petty-cash/deposit?error=' . urlencode('Amount must be greater than zero')));
            return;
        }

        if (empty($description)) {
            header('Location: ' . base_url('dashboard/petty-cash/deposit?error=' . urlencode('Description is required')));
            return;
        }

        try {
            $account = $this->pettyCash->getAccount();
            $limit = (float)$account['limit_amount'];
            $currentBalance = (float)$account['balance'];
            $newBalance = $currentBalance + $amount;

            if ($newBalance > $limit) {
                header('Location: ' . base_url('dashboard/petty-cash/deposit?error=' . urlencode('Deposit would exceed the petty cash limit of KES ' . number_format($limit, 2))));
                return;
            }

            $this->pettyCash->addTransaction([
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'description' => $description,
                'processed_by' => $user['id'],
                'notes' => $notes,
            ]);

            header('Location: ' . base_url('dashboard/petty-cash?success=' . urlencode('Deposit of KES ' . number_format($amount, 2) . ' added successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/petty-cash/deposit?error=' . urlencode($e->getMessage())));
        }
    }

    public function linkExpense(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager', 'operation_manager', 'cashier']);

        $expenseId = (int)$request->input('expense_id');
        $amount = (float)$request->input('amount', 0);

        if (!$expenseId || $amount <= 0) {
            header('Location: ' . base_url('dashboard/petty-cash?error=' . urlencode('Invalid expense or amount')));
            return;
        }

        $expense = $this->expenses->find($expenseId);
        if (!$expense) {
            header('Location: ' . base_url('dashboard/petty-cash?error=' . urlencode('Expense not found')));
            return;
        }

        try {
            if (!$this->pettyCash->canWithdraw($amount)) {
                $available = $this->pettyCash->getAvailableBalance();
                header('Location: ' . base_url('dashboard/petty-cash?error=' . urlencode('Insufficient petty cash. Available balance: KES ' . number_format($available, 2))));
                return;
            }

            $user = Auth::user();
            $this->pettyCash->addTransaction([
                'transaction_type' => 'expense',
                'amount' => $amount,
                'description' => 'Expense: ' . $expense['description'],
                'expense_id' => $expenseId,
                'processed_by' => $user['id'] ?? null,
            ]);

            header('Location: ' . base_url('dashboard/petty-cash?success=' . urlencode('Expense linked to petty cash successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/petty-cash?error=' . urlencode($e->getMessage())));
        }
    }

    public function settings(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        if ($request->method() === 'POST') {
            $this->updateSettings($request);
            return;
        }

        $account = $this->pettyCash->getAccount();
        $users = $this->users->all();

        $this->view('dashboard/petty-cash/settings', [
            'account' => $account,
            'users' => $users,
        ]);
    }

    public function updateSettings(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'finance_manager']);

        $data = [
            'account_name' => trim($request->input('account_name', 'Petty Cash')),
            'limit_amount' => (float)$request->input('limit_amount', 2000),
            'custodian_id' => $request->input('custodian_id') ? (int)$request->input('custodian_id') : null,
            'status' => $request->input('status', 'active'),
        ];

        try {
            $this->pettyCash->updateAccount($data);
            header('Location: ' . base_url('dashboard/petty-cash/settings?success=' . urlencode('Petty cash settings updated successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/petty-cash/settings?error=' . urlencode('Failed to update settings')));
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

