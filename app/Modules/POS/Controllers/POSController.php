<?php

namespace App\Modules\POS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\FolioRepository;
use App\Repositories\PosItemRepository;
use App\Repositories\PosSaleRepository;
use App\Repositories\PosTillRepository;
use App\Repositories\SalesReportRepository;
use App\Repositories\ReservationRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use App\Services\Payments\MpesaService;
use App\Support\Auth;
use Exception;

class POSController extends Controller
{
    protected PosItemRepository $items;
    protected PosSaleRepository $sales;
    protected PosTillRepository $tills;
    protected ReservationRepository $reservations;
    protected FolioRepository $folios;
    protected NotificationService $notifications;
    protected InventoryService $inventoryService;

    public function __construct()
    {
        $this->items = new PosItemRepository();
        $this->sales = new PosSaleRepository();
        $this->tills = new PosTillRepository();
        $this->reservations = new ReservationRepository();
        $this->folios = new FolioRepository();
        $this->notifications = new NotificationService();
        $this->inventoryService = new InventoryService();
    }

	public function dashboard(Request $request): void
	{
		Auth::requireRoles(['admin', 'operation_manager', 'finance_manager', 'cashier', 'service_agent']);

		$start = $request->input('start') ?: date('Y-m-01');
		$end = $request->input('end') ?: date('Y-m-d');

		$reports = new SalesReportRepository();
		$summary = $reports->summary($start, $end);
		$payments = $reports->paymentBreakdown($start, $end);
		$trend = $reports->trend($start, $end);
		$topItems = $reports->topItems($start, $end, 5);
		$topCategories = $reports->topCategories($start, $end, 5);
		$topStaff = $reports->topStaff($start, $end, 5);

		$this->view('dashboard/pos/dashboard', [
			'filters' => ['start' => $start, 'end' => $end],
			'summary' => $summary,
			'payments' => $payments,
			'trend' => $trend,
			'topItems' => $topItems,
			'topCategories' => $topCategories,
			'topStaff' => $topStaff,
		]);
	}

    public function index(): void
    {
        Auth::requireRoles(['admin', 'cashier', 'service_agent']);

        // Pull POS items from Inventory (categories + stock + mapping to POS items).
        // We do not hide unavailable items here; they will be shown but greyed out in the UI.
        $invRepo = new \App\Repositories\InventoryRepository();
        $categories = $invRepo->posEnabledByCategory(false);

        $user = Auth::user();
        $this->view('dashboard/pos/index', [
            'categories' => $categories,
            'locations' => $this->inventoryService->locations(),
            'checkedInGuests' => $this->reservations->checkedInGuests(),
            'user' => $user,
        ]);
    }

    public function sale(Request $request): void
    {
        Auth::requireRoles(['admin', 'cashier', 'service_agent']);

        $itemIds = $request->input('item_ids', []);
        $quantities = $request->input('quantities', []);
        $prices = $request->input('prices', []);

        // Validate items exist before processing
        $validItemIds = [];
        $invalidItemIds = [];
        
        if (!empty($itemIds)) {
            // Convert to integers and filter out invalid IDs
            $itemIds = array_map('intval', $itemIds);
            $itemIds = array_filter($itemIds, function($id) { return $id > 0; });
            
            if (!empty($itemIds)) {
                $validItemIds = $this->items->validateItems($itemIds);
                $invalidItemIds = array_diff($itemIds, $validItemIds);
                
                if (!empty($invalidItemIds)) {
                    header('Location: ' . base_url('staff/dashboard/pos?error=' . urlencode('Invalid or deleted item IDs: ' . implode(', ', $invalidItemIds) . '. Please refresh the page to remove these items.')));
                    return;
                }
            }
        }

        // Build lines array, only including valid items
        // Note: We preserve original array indices to match with quantities/prices
        $lines = [];
        foreach ($itemIds as $index => $itemId) {
            // Double-check item is valid
            if (!in_array((int)$itemId, $validItemIds, true)) {
                continue;
            }
            
            $qty = isset($quantities[$index]) ? (int)$quantities[$index] : 1;
            $price = isset($prices[$index]) ? (float)$prices[$index] : 0;
            
            if ($qty <= 0 || $price <= 0) {
                continue;
            }
            
            $lines[] = [
                'item_id' => (int)$itemId,
                'quantity' => $qty,
                'price' => $price,
                'line_total' => $qty * $price,
            ];
        }

        if (empty($lines)) {
            header('Location: ' . base_url('staff/dashboard/pos?error=Add%20items%20to%20order'));
            return;
        }

        $user = Auth::user();
        $paymentType = $request->input('payment_type', 'cash');
        $customerType = $request->input('customer_type', 'walkin');
        $reservationReference = trim($request->input('reservation_reference', ''));
        $reservationId = null;

        if ($reservationReference !== '') {
            $reservation = $this->reservations->findByReference($reservationReference);
            if ($reservation) {
                $reservationId = (int)$reservation['id'];
            } else {
                header('Location: ' . base_url('staff/dashboard/pos?error=Reservation%20not%20found'));
                return;
            }
        }

        // If payment type is room charge, reservation must be linked
        if ($paymentType === 'room' && !$reservationId) {
            header('Location: ' . base_url('staff/dashboard/pos?error=Room%20charge%20requires%20a%20guest%20selection'));
            return;
        }

        $total = array_sum(array_column($lines, 'line_total'));

        // Handle payment using unified payment processing service
        $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
        
        $mpesaPhone = null;
        $mpesaCheckoutRequestId = null;
        $mpesaMerchantRequestId = null;
        $mpesaStatus = null;
        
        try {
            $reference = 'POS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $paymentOptions = [
                'reference' => $reference,
                'description' => 'POS Sale Payment',
                'reservation_id' => $reservationId,
            ];
            
            // Add phone for M-Pesa
            if ($paymentType === 'mpesa') {
                $mpesaPhone = trim($request->input('mpesa_phone', ''));
                if (empty($mpesaPhone)) {
                    header('Location: ' . base_url('staff/dashboard/pos?error=Phone%20number%20is%20required%20for%20M-Pesa%20payment'));
                    return;
                }
                $paymentOptions['phone'] = $mpesaPhone;
            }
            
            $paymentResult = $paymentProcessor->processPayment($paymentType, $total, $paymentOptions);
            
            $mpesaPhone = $paymentResult['mpesa_phone'] ?? null;
            $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
            $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;
            $mpesaStatus = $paymentResult['mpesa_status'] ?? null;
            
            // Create payment transaction record for M-Pesa
            if ($paymentType === 'mpesa' && $mpesaCheckoutRequestId) {
                $this->createPaymentTransaction('pos_sale', 0, $reference, 'mpesa', $total, $mpesaPhone, $mpesaCheckoutRequestId, $mpesaMerchantRequestId);
            }
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/pos?error=' . urlencode('Payment processing failed: ' . $e->getMessage())));
            return;
        }

        try {
            $saleId = $this->sales->create([
                'user_id' => $user['id'],
                'till_id' => null, // No longer using till - staff member is tracked via user_id
                'payment_type' => $paymentType,
                'total' => $total,
                'notes' => $request->input('notes'),
                'reservation_id' => $reservationId,
                'mpesa_phone' => $mpesaPhone,
                'mpesa_checkout_request_id' => $mpesaCheckoutRequestId,
                'mpesa_merchant_request_id' => $mpesaMerchantRequestId,
                'mpesa_status' => $mpesaStatus,
            ], $lines);

            // Update payment transaction with sale ID
            if ($mpesaCheckoutRequestId) {
                $this->updatePaymentTransactionReference($mpesaCheckoutRequestId, $saleId, $this->sales->findById($saleId)['reference'] ?? '');
            }

            // Only deduct inventory and create folio entries if payment is not pending (cash, card, etc.)
            // For M-Pesa, wait for callback confirmation
            if ($paymentType !== 'mpesa' || $mpesaStatus === 'completed') {
                // Auto-select location for each item based on stock availability
                // For each line, find the best location (highest stock) for its inventory components
                $this->deductInventory($lines, null, 'POS #' . $saleId);

                if ($reservationId) {
                    $folio = $this->folios->findByReservation($reservationId);
                    if (!$folio) {
                        $folioId = $this->folios->create($reservationId);
                        $folio = $this->folios->findByReservation($reservationId);
                    }
                    $this->folios->addEntry((int)$folio['id'], 'POS sale #' . $saleId, $total, 'charge', 'pos');
                }
            }

            $this->notifications->notifyRole('finance_manager', 'POS Sale Recorded', sprintf(
                '%s processed a sale of KES %s via POS (%s).',
                $user['name'],
                number_format($total, 2),
                $paymentType === 'mpesa' ? 'M-Pesa - Pending' : strtoupper($paymentType)
            ));

            // Redirect to receipt page
            header('Location: ' . base_url('staff/dashboard/pos/receipt?id=' . $saleId));
        } catch (Exception $e) {
            header('Location: ' . base_url('staff/dashboard/pos?error=' . urlencode($e->getMessage())));
        }
    }

    protected function deductInventory(array $lines, ?int $defaultLocationId, string $reference): void
    {
        $invRepo = new \App\Repositories\InventoryRepository();
        
        foreach ($lines as $line) {
            $components = $this->fetchComponents($line['item_id']);
            foreach ($components as $component) {
                $qty = $component['quantity_per_sale'] * $line['quantity'];
                if ($qty > 0) {
                    $inventoryItemId = (int)$component['inventory_item_id'];
                    
                    // Auto-select best location for this inventory item (location with highest stock)
                    $locationId = $invRepo->findBestLocationForItem($inventoryItemId);
                    
                    // Fallback to default location if no location found
                    if (!$locationId && $defaultLocationId) {
                        $locationId = $defaultLocationId;
                    } elseif (!$locationId) {
                        // Last resort: use first available location
                        $locations = $this->inventoryService->locations();
                        $locationId = !empty($locations) ? (int)$locations[0]['id'] : null;
                    }
                    
                    if ($locationId) {
                        $this->inventoryService->deductStock($inventoryItemId, $locationId, $qty, $reference, 'POS sale');
                    }
                }
            }
        }
    }

    protected function fetchComponents(int $posItemId): array
    {
        $stmt = db()->prepare('SELECT * FROM pos_item_components WHERE pos_item_id = :id');
        $stmt->execute(['id' => $posItemId]);

        return $stmt->fetchAll();
    }

    public function receipt(Request $request): void
    {
        Auth::requireRoles(['admin', 'cashier', 'service_agent']);

        $saleId = (int)$request->input('id', 0);
        if (!$saleId) {
            header('Location: ' . base_url('staff/dashboard/pos?error=Sale%20ID%20required'));
            return;
        }

        // Always fetch fresh data from database to get latest payment status
        $sale = $this->sales->findById($saleId);
        if (!$sale) {
            header('Location: ' . base_url('staff/dashboard/pos?error=Sale%20not%20found'));
            return;
        }

        $items = $this->sales->getItems($saleId);
        $user = Auth::user();

        $this->view('dashboard/pos/receipt', [
            'sale' => $sale,
            'items' => $items,
            'user' => $user,
        ]);
    }

    protected function createPaymentTransaction(string $type, int $referenceId, string $referenceCode, string $method, float $amount, ?string $phone, ?string $checkoutRequestId, ?string $merchantRequestId): void
    {
        $stmt = db()->prepare('
            INSERT INTO payment_transactions (transaction_type, reference_id, reference_code, payment_method, amount, phone_number, checkout_request_id, merchant_request_id, status)
            VALUES (:type, :ref_id, :ref_code, :method, :amount, :phone, :checkout_id, :merchant_id, :status)
        ');
        $stmt->execute([
            'type' => $type,
            'ref_id' => $referenceId,
            'ref_code' => $referenceCode,
            'method' => $method,
            'amount' => $amount,
            'phone' => $phone,
            'checkout_id' => $checkoutRequestId,
            'merchant_id' => $merchantRequestId,
            'status' => 'pending',
        ]);
    }

    protected function updatePaymentTransactionReference(string $checkoutRequestId, int $referenceId, string $referenceCode): void
    {
        $stmt = db()->prepare('
            UPDATE payment_transactions 
            SET reference_id = :ref_id, reference_code = :ref_code
            WHERE checkout_request_id = :checkout_id
        ');
        $stmt->execute([
            'ref_id' => $referenceId,
            'ref_code' => $referenceCode,
            'checkout_id' => $checkoutRequestId,
        ]);
    }
}

