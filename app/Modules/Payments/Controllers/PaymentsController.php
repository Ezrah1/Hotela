<?php

namespace App\Modules\Payments\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PaymentRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\SupplierRepository;
use App\Support\Auth;

class PaymentsController extends Controller
{
    protected PaymentRepository $payments;
    protected ExpenseRepository $expenses;
    protected SupplierRepository $suppliers;

    public function __construct()
    {
        $this->payments = new PaymentRepository();
        $this->expenses = new ExpenseRepository();
        $this->suppliers = new SupplierRepository();
    }
    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'cashier', 'receptionist']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');
        $type = $request->input('type', ''); // 'pos', 'booking', or ''
        $paymentMethod = $request->input('payment_method', '');

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        // Get POS payments
        $posPayments = $this->getPosPayments($start, $end, $paymentMethod);
        
        // Get booking/folio payments
        $folioPayments = $this->getFolioPayments($start, $end, $paymentMethod);

        // Combine and filter by type
        $allPayments = [];
        if ($type === 'pos' || $type === '') {
            foreach ($posPayments as $payment) {
                $allPayments[] = array_merge($payment, ['source' => 'pos']);
            }
        }
        if ($type === 'booking' || $type === '') {
            foreach ($folioPayments as $payment) {
                $allPayments[] = array_merge($payment, ['source' => 'booking']);
            }
        }

        // Sort by date descending
        usort($allPayments, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Calculate summary
        $summary = $this->calculateSummary($allPayments);

        $this->view('dashboard/payments/index', [
            'payments' => $allPayments,
            'summary' => $summary,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'type' => $type,
                'payment_method' => $paymentMethod,
            ],
        ]);
    }

    protected function sanitizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function getPosPayments(string $start, string $end, ?string $paymentMethod = null): array
    {
        $params = [
            'start' => $start,
            'end' => $end,
        ];

        $sql = "
            SELECT 
                ps.id,
                ps.reference,
                ps.payment_type,
                ps.total AS amount,
                ps.created_at,
                u.name AS processed_by,
                r.reference AS reservation_reference,
                r.guest_name,
                'POS Sale' AS description
            FROM pos_sales ps
            LEFT JOIN users u ON u.id = ps.user_id
            LEFT JOIN reservations r ON r.id = ps.reservation_id
            WHERE DATE(ps.created_at) >= :start AND DATE(ps.created_at) <= :end
        ";

        if ($paymentMethod) {
            $sql .= ' AND ps.payment_type = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }

        $sql .= ' ORDER BY ps.created_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function getFolioPayments(string $start, string $end, ?string $paymentMethod = null): array
    {
        $params = [
            'start' => $start,
            'end' => $end,
        ];

        $sql = "
            SELECT 
                fe.id,
                CONCAT('FOLIO-', f.id) AS reference,
                COALESCE(fe.source, 'cash') AS payment_type,
                fe.amount,
                fe.created_at,
                NULL AS processed_by,
                r.reference AS reservation_reference,
                r.guest_name,
                fe.description
            FROM folio_entries fe
            INNER JOIN folios f ON f.id = fe.folio_id
            INNER JOIN reservations r ON r.id = f.reservation_id
            WHERE fe.type = 'payment'
            AND DATE(fe.created_at) >= :start AND DATE(fe.created_at) <= :end
        ";

        if ($paymentMethod) {
            $sql .= ' AND COALESCE(fe.source, \'cash\') = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }


        $sql .= ' ORDER BY fe.created_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function calculateSummary(array $payments): array
    {
        $total = 0;
        $byMethod = [];
        $bySource = ['pos' => 0, 'booking' => 0];

        foreach ($payments as $payment) {
            $amount = (float)$payment['amount'];
            $total += $amount;
            
            $method = $payment['payment_type'] ?? 'cash';
            $byMethod[$method] = ($byMethod[$method] ?? 0) + $amount;
            
            $source = $payment['source'] ?? 'pos';
            $bySource[$source] = ($bySource[$source] ?? 0) + $amount;
        }

        return [
            'total' => $total,
            'count' => count($payments),
            'by_method' => $byMethod,
            'by_source' => $bySource,
        ];
    }

    public function record(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        if ($request->method() === 'POST') {
            $this->store($request);
            return;
        }

        $paymentType = $request->input('type', 'expense');
        $expenseId = $request->input('expense_id') ? (int)$request->input('expense_id') : null;
        $supplierId = $request->input('supplier_id') ? (int)$request->input('supplier_id') : null;

        $expense = null;
        $supplier = null;

        if ($expenseId) {
            $expense = $this->expenses->find($expenseId);
        }

        if ($supplierId) {
            $supplier = $this->suppliers->find($supplierId);
        }

        $this->view('dashboard/payments/record', [
            'paymentType' => $paymentType,
            'expense' => $expense,
            'supplier' => $supplier,
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $user = Auth::user();
        if (!$user) {
            header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('User not authenticated')));
            return;
        }

        $paymentType = $request->input('payment_type');
        $amount = (float)$request->input('amount', 0);
        $paymentDate = $this->sanitizeDate($request->input('payment_date')) ?? date('Y-m-d');
        $paymentMethod = $request->input('payment_method', 'bank_transfer');
        $transactionReference = trim($request->input('transaction_reference', ''));
        $notes = trim($request->input('notes', ''));

        if ($amount <= 0) {
            header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Amount must be greater than zero')));
            return;
        }

        $data = [
            'payment_type' => $paymentType,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_date' => $paymentDate,
            'transaction_reference' => $transactionReference ?: null,
            'notes' => $notes ?: null,
            'processed_by' => $user['id'],
            'status' => 'completed',
        ];

        // Handle different payment types
        if ($paymentType === 'expense') {
            $expenseId = $request->input('expense_id') ? (int)$request->input('expense_id') : null;
            if (!$expenseId) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Expense ID is required')));
                return;
            }
            $expense = $this->expenses->find($expenseId);
            if (!$expense) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Expense not found')));
                return;
            }
            $data['expense_id'] = $expenseId;
            
            // Mark expense as paid
            $this->expenses->update($expenseId, [
                'status' => 'paid',
                'paid_by' => $user['id'],
                'paid_at' => date('Y-m-d H:i:s'),
            ]);
        } elseif ($paymentType === 'bill') {
            $billId = $request->input('bill_id') ? (int)$request->input('bill_id') : null;
            if (!$billId) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Bill ID is required')));
                return;
            }
            $bill = $this->expenses->find($billId);
            if (!$bill) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Bill not found')));
                return;
            }
            $data['bill_id'] = $billId;
            
            // Mark bill as paid
            $this->expenses->update($billId, [
                'status' => 'paid',
                'paid_by' => $user['id'],
                'paid_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Update supplier balance if bill has supplier
            if (!empty($bill['supplier_id'])) {
                $supplier = $this->suppliers->find($bill['supplier_id']);
                if ($supplier) {
                    $newBalance = (float)($supplier['current_balance'] ?? 0) - $amount;
                    $this->suppliers->update($bill['supplier_id'], [
                        'current_balance' => max(0, $newBalance), // Don't allow negative
                    ]);
                }
            }
        } elseif ($paymentType === 'supplier') {
            $supplierId = $request->input('supplier_id') ? (int)$request->input('supplier_id') : null;
            if (!$supplierId) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Supplier ID is required')));
                return;
            }
            $supplier = $this->suppliers->find($supplierId);
            if (!$supplier) {
                header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Supplier not found')));
                return;
            }
            $data['supplier_id'] = $supplierId;
            
            // Update supplier balance
            $newBalance = (float)($supplier['current_balance'] ?? 0) - $amount;
            $this->suppliers->update($supplierId, [
                'current_balance' => max(0, $newBalance), // Don't allow negative
            ]);
        }

        // Add cheque/bank details if applicable
        if ($paymentMethod === 'cheque') {
            $data['cheque_number'] = trim($request->input('cheque_number', ''));
        }
        if ($paymentMethod === 'bank_transfer') {
            $data['bank_name'] = trim($request->input('bank_name', ''));
            $data['account_number'] = trim($request->input('account_number', ''));
        }

        try {
            $this->payments->create($data);
            header('Location: ' . base_url('dashboard/payments?success=' . urlencode('Payment recorded successfully')));
        } catch (\Exception $e) {
            header('Location: ' . base_url('dashboard/payments/record?error=' . urlencode('Failed to record payment: ' . $e->getMessage())));
        }
    }

    public function manage(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');
        $paymentType = $request->input('payment_type', '');
        $paymentMethod = $request->input('payment_method', '');
        $status = $request->input('status', '');

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        $payments = $this->payments->all($start, $end, $paymentType ?: null, $paymentMethod ?: null, $status ?: null);
        $summary = $this->payments->getSummary($start, $end);

        $this->view('dashboard/payments/manage', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'status' => $status,
            ],
        ]);
    }
}

