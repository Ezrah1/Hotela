<?php

namespace App\Modules\CashBanking\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\CashBankingRepository;
use App\Repositories\CashShiftRepository;
use App\Repositories\BankingDayRepository;
use App\Services\FileUploadService;
use App\Support\Auth;

class CashBankingController extends Controller
{
    protected CashShiftRepository $shifts;
    protected CashBankingRepository $banking;
    protected BankingDayRepository $bankingDays;
    protected FileUploadService $fileUpload;

    public function __construct()
    {
        $this->shifts = new CashShiftRepository();
        $this->banking = new CashBankingRepository();
        $this->bankingDays = new BankingDayRepository();
        $this->fileUpload = new FileUploadService();
    }

    public function index(): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'cashier']);

        $user = Auth::user();
        $today = date('Y-m-d');
        
        // Get current user's open shift
        $openShift = $this->shifts->getOpenShift((int)$user['id'], $today);
        
        // If no open shift, create one
        if (!$openShift) {
            $shiftId = $this->shifts->create((int)$user['id'], $today);
            $openShift = $this->shifts->findById($shiftId);
        }

        // Calculate cash from POS sales and booking payments
        $cashBreakdown = $this->shifts->getCashBreakdownForDate((int)$user['id'], $today);
        $cashCalculated = $cashBreakdown['total'];

        // Get unbanked cash total (for Finance Manager)
        $unbankedTotal = 0;
        if (in_array($user['role_key'] ?? ($user['role'] ?? ''), ['admin', 'finance_manager'], true)) {
            $unbankedTotal = $this->banking->getTotalUnbankedCash();
        }

        // Get batches by status
        $unbankedBatches = [];
        $readyBatches = [];
        $bankedBatches = [];
        
        if (in_array($user['role_key'] ?? ($user['role'] ?? ''), ['admin', 'finance_manager'], true)) {
            $unbankedBatches = $this->banking->getBatchesByStatus('unbanked');
            $readyBatches = $this->banking->getBatchesByStatus('ready_for_banking');
            $bankedBatches = $this->banking->getBatchesByStatus('banked');
        }

        $this->view('dashboard/cash-banking/index', [
            'openShift' => $openShift,
            'cashCalculated' => $cashCalculated,
            'cashBreakdown' => $cashBreakdown,
            'unbankedTotal' => $unbankedTotal,
            'unbankedBatches' => $unbankedBatches,
            'readyBatches' => $readyBatches,
            'bankedBatches' => $bankedBatches,
            'user' => $user,
        ]);
    }

    public function closeShift(Request $request): void
    {
        Auth::requireRoles(['admin', 'cashier']);

        $user = Auth::user();
        $shiftId = (int)$request->input('shift_id');
        $cashDeclared = (float)$request->input('cash_declared', 0);
        $notes = trim($request->input('notes', ''));

        if ($cashDeclared < 0) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=Invalid%20cash%20amount'));
            return;
        }

        try {
            $this->shifts->closeShift($shiftId, $cashDeclared, (int)$user['id'], $notes ?: null);
            
            // Notify Finance Manager
            $shift = $this->shifts->findById($shiftId);
            $this->notifyFinanceManager('Shift Closed', sprintf(
                '%s closed their shift with KES %s declared cash.',
                $user['name'],
                number_format($cashDeclared, 2)
            ));

            header('Location: ' . base_url('staff/dashboard/cash-banking?success=Shift%20closed%20successfully'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=' . urlencode($e->getMessage())));
        }
    }

    public function createBatch(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $date = $request->input('date', date('Y-m-d'));
        $shiftIds = $request->input('shift_ids', []);

        if (empty($shiftIds) || !is_array($shiftIds)) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=No%20shifts%20selected'));
            return;
        }

        try {
            $batchId = $this->banking->createBatch($date, array_map('intval', $shiftIds));
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&success=Batch%20created'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=' . urlencode($e->getMessage())));
        }
    }

    public function batch(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $batchId = (int)$request->input('id');
        $batch = $this->banking->findById($batchId);

        if (!$batch) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=Batch%20not%20found'));
            return;
        }

        $shifts = $this->banking->getShiftsForBatch($batchId);
        $reconciliation = $this->banking->getReconciliationForBatch($batchId);

        // Calculate breakdown for all shifts in batch
        $totalPosCash = 0;
        $totalBookingCash = 0;
        foreach ($shifts as $shift) {
            $breakdown = $this->shifts->getCashBreakdownForDate((int)$shift['user_id'], $shift['shift_date']);
            $totalPosCash += $breakdown['pos_cash'];
            $totalBookingCash += $breakdown['booking_cash'];
        }

        $this->view('dashboard/cash-banking/batch', [
            'batch' => $batch,
            'shifts' => $shifts,
            'reconciliation' => $reconciliation,
            'cashBreakdown' => [
                'pos_cash' => $totalPosCash,
                'booking_cash' => $totalBookingCash,
                'total' => $totalPosCash + $totalBookingCash,
            ],
        ]);
    }

    public function reconcile(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $batchId = (int)$request->input('batch_id');
        $cashDeclared = (float)$request->input('cash_declared', 0);
        $cashCalculated = (float)$request->input('cash_calculated', 0);
        $adjustmentAmount = $request->input('adjustment_amount') ? (float)$request->input('adjustment_amount') : null;
        $adjustmentReason = trim($request->input('adjustment_reason', ''));
        $notes = trim($request->input('notes', ''));

        $user = Auth::user();

        try {
            $reconciliationId = $this->banking->createReconciliation(
                $batchId,
                (int)$user['id'],
                $cashDeclared,
                $cashCalculated,
                $adjustmentAmount,
                $adjustmentReason ?: null,
                $notes ?: null
            );

            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&success=Reconciliation%20created'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function approveReconciliation(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $reconciliationId = (int)$request->input('reconciliation_id');

        try {
            $this->banking->approveReconciliation($reconciliationId);
            
            $reconciliation = $this->banking->getReconciliation($reconciliationId);
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $reconciliation['batch_id'] . '&success=Reconciliation%20approved'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/cash-banking?error=' . urlencode($e->getMessage())));
        }
    }

    public function markBanked(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $batchId = (int)$request->input('batch_id');
        $notes = trim($request->input('notes', ''));

        $user = Auth::user();

        // Handle deposit slip upload
        $depositSlipPath = null;
        if (isset($_FILES['deposit_slip']) && $_FILES['deposit_slip']['error'] === UPLOAD_ERR_OK) {
            try {
                $depositSlipPath = $this->fileUpload->uploadDocument($_FILES['deposit_slip'], 'deposit-slips');
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&error=' . urlencode('File upload failed: ' . $e->getMessage())));
                return;
            }
        }

        if (!$depositSlipPath) {
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&error=Deposit%20slip%20required'));
            return;
        }

        try {
            $this->banking->markAsBanked($batchId, (int)$user['id'], $depositSlipPath, $notes ?: null);
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&success=Cash%20marked%20as%20banked'));
        } catch (\Exception $e) {
            header('Location: ' . base_url('staff/dashboard/cash-banking/batch?id=' . $batchId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function unbankedShifts(): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $shifts = $this->shifts->getUnbankedShifts();
        $shiftsByDate = [];

        foreach ($shifts as $shift) {
            $date = $shift['shift_date'];
            if (!isset($shiftsByDate[$date])) {
                $shiftsByDate[$date] = [];
            }
            $shiftsByDate[$date][] = $shift;
        }

        $this->view('dashboard/cash-banking/unbanked-shifts', [
            'shiftsByDate' => $shiftsByDate,
        ]);
    }

    protected function notifyFinanceManager(string $title, string $message): void
    {
        $notificationService = new \App\Services\Notifications\NotificationService();
        $notificationService->notifyRole('finance_manager', $title, $message);
    }
}

