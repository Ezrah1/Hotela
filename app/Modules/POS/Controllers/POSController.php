<?php

namespace App\Modules\POS\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\FolioRepository;
use App\Repositories\OrderRepository;
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
		// POS Dashboard is restricted to managerial positions and upwards only
		Auth::requireRoles(['director', 'admin', 'operation_manager', 'finance_manager', 'receptionist']);

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
        Auth::requireRoles(['director', 'admin', 'cashier', 'service_agent', 'receptionist']);

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
        Auth::requireRoles(['director', 'admin', 'cashier', 'service_agent', 'receptionist']);

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
                
                // Update production costs for all POS items in the sale (in case ingredient costs changed)
                $invRepo = new \App\Repositories\InventoryRepository();
                foreach ($lines as $line) {
                    try {
                        $invRepo->updateProductionCost((int)$line['item_id']);
                    } catch (\Exception $e) {
                        // Log but don't fail the sale
                        error_log('Failed to update production cost for POS item ' . $line['item_id'] . ': ' . $e->getMessage());
                    }
                }

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

            // Create corresponding order entry for order management system
            try {
                $sale = $this->sales->findById($saleId);
                $saleItems = $this->sales->getItems($saleId);
                
                $reservation = null;
                $customerName = null;
                $customerPhone = null;
                $customerEmail = null;
                $roomNumber = null;
                
                if ($reservationId) {
                    $reservation = $this->reservations->findById($reservationId);
                    if ($reservation) {
                        $customerName = $reservation['guest_name'] ?? null;
                        $customerPhone = $reservation['guest_phone'] ?? null;
                        $customerEmail = $reservation['guest_email'] ?? null;
                        $roomNumber = $reservation['room_number'] ?? null;
                    }
                }
                
                $orderRepo = new OrderRepository();
                $orderItems = [];
                
                foreach ($saleItems as $saleItem) {
                    $posItem = $this->items->find((int)$saleItem['item_id']);
                    $orderItems[] = [
                        'pos_item_id' => (int)$saleItem['item_id'],
                        'item_name' => $posItem['name'] ?? 'Item #' . $saleItem['item_id'],
                        'quantity' => (float)$saleItem['quantity'],
                        'unit_price' => (float)$saleItem['price'],
                        'line_total' => (float)$saleItem['line_total'],
                    ];
                }
                
                // Determine order status based on payment
                // All orders start as 'pending' or 'confirmed' - staff will process them
                // Only auto-complete if payment is confirmed (M-Pesa completed)
                if ($mpesaStatus === 'completed' || ($sale['payment_status'] ?? '') === 'paid') {
                    $orderStatus = 'confirmed'; // Payment confirmed, ready for processing
                } else {
                    $orderStatus = 'pending'; // Payment pending, wait for confirmation
                }
                
                $orderId = $orderRepo->create([
                    'reference' => $sale['reference'] ?? $reference,
                    'order_type' => $reservationId ? 'room_service' : 'pos_order',
                    'source' => 'pos',
                    'user_id' => $user['id'],
                    'reservation_id' => $reservationId,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'customer_email' => $customerEmail,
                    'room_number' => $roomNumber,
                    'service_type' => $reservationId ? 'room_service' : 'dine_in',
                    'status' => $orderStatus,
                    // All payments start as 'pending' - staff must confirm payment was received
                    'payment_status' => $sale['payment_status'] ?? 'pending',
                    'payment_type' => $paymentType,
                    'total' => $total,
                    'notes' => $request->input('notes'),
                    'items' => $orderItems,
                ]);
                
                // Send notifications for new order (only if status is pending, not completed)
                if ($orderStatus === 'pending') {
                    $orderData = $orderRepo->findById($orderId);
                    if ($orderData) {
                        // Check if order contains items that require kitchen preparation
                        $requiresKitchen = $this->orderRequiresKitchen($lines);
                        
                        // Only notify kitchen if order contains items that need preparation
                        if ($requiresKitchen) {
                            $this->notifications->notifyRole('kitchen', 'New Order', 
                                sprintf('New order #%s received. Total: KES %s', $orderData['reference'], number_format($total, 2)),
                                ['order_id' => $orderId, 'reference' => $orderData['reference']]
                            );
                        }
                        
                        $this->notifications->notifyRole('service_agent', 'New Order', 
                            sprintf('New order #%s received. %s', $orderData['reference'], 
                                $orderData['service_type'] === 'room_service' ? 'Room service order.' : 'Customer order.'),
                            ['order_id' => $orderId, 'reference' => $orderData['reference']]
                        );
                        $this->notifications->notifyRole('operation_manager', 'New Order', 
                            sprintf('New order #%s created via POS', $orderData['reference']),
                            ['order_id' => $orderId, 'reference' => $orderData['reference']]
                        );
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the sale
                error_log('Failed to create order entry for POS sale #' . $saleId . ': ' . $e->getMessage());
            }

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
                // Calculate base quantity needed
                $baseQty = $component['quantity_per_sale'] * $line['quantity'];
                
                if ($baseQty > 0) {
                    $inventoryItemId = (int)$component['inventory_item_id'];
                    
                    // Apply unit conversion if needed
                    $sourceUnit = $component['source_unit'] ?? null;
                    $targetUnit = $component['target_unit'] ?? $component['inventory_unit'] ?? null;
                    $conversionFactor = (float)($component['conversion_factor'] ?? 1.0);
                    
                    // Convert quantity to inventory unit
                    $convertedQty = $invRepo->convertQuantity($baseQty, $sourceUnit, $targetUnit, $conversionFactor);
                    
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
                        $this->inventoryService->deductStock($inventoryItemId, $locationId, $convertedQty, $reference, 'POS sale');
                    }
                }
            }
        }
    }

    protected function fetchComponents(int $posItemId): array
    {
        // Get components with unit conversion support
        $invRepo = new \App\Repositories\InventoryRepository();
        return $invRepo->getPosComponents($posItemId);
    }

    /**
     * Check if an order contains items that require kitchen preparation
     * Kitchen items are typically: Breakfast, Lunch, Dinner, Snacks, Specials
     * Non-kitchen items: Soft Drinks, Alcohol, Beverages, etc.
     */
    protected function orderRequiresKitchen(array $lines): bool
    {
        if (empty($lines)) {
            return false;
        }

        // Categories that require kitchen preparation
        $kitchenCategories = [
            'breakfast', 'lunch', 'dinner', 'snacks', 'specials', 
            'food', 'meals', 'appetizers', 'desserts', 'soups',
            'salads', 'sandwiches', 'burgers', 'pizza', 'pasta'
        ];

        $db = db();
        $itemIds = array_map(function($line) {
            return (int)($line['item_id'] ?? 0);
        }, $lines);
        $itemIds = array_filter($itemIds, function($id) { return $id > 0; });

        if (empty($itemIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "
            SELECT DISTINCT LOWER(pc.name) AS category_name
            FROM pos_items pi
            INNER JOIN pos_categories pc ON pc.id = pi.category_id
            WHERE pi.id IN ($placeholders)
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($itemIds);
        $categories = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Check if any category matches kitchen categories
        foreach ($categories as $category) {
            $categoryLower = strtolower(trim($category));
            foreach ($kitchenCategories as $kitchenCat) {
                if (strpos($categoryLower, $kitchenCat) !== false) {
                    return true; // Found at least one kitchen item
                }
            }
        }

        return false; // No kitchen items found
    }

    public function receipt(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'cashier', 'service_agent', 'receptionist']);

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
    
    // ========== POS Item Management ==========
    
    public function items(): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $items = $this->items->all();
        $categories = $this->items->categories();
        
        $this->view('dashboard/pos/items/index', [
            'items' => $items,
            'categories' => $categories,
        ]);
    }
    
    public function createItem(): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $categories = $this->items->categories();
        $inventoryItems = (new \App\Repositories\InventoryRepository())->allItems();
        
        $this->view('dashboard/pos/items/create', [
            'categories' => $categories,
            'inventoryItems' => $inventoryItems,
        ]);
    }
    
    public function storeItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $data = [
            'category_id' => (int)$request->input('category_id'),
            'name' => trim($request->input('name', '')),
            'price' => (float)$request->input('price', 0),
            'sku' => trim($request->input('sku', '')),
            'tracked' => $request->input('tracked', false),
            'is_inventory_item' => $request->input('is_inventory_item', false),
        ];
        
        if (empty($data['name']) || $data['category_id'] <= 0) {
            header('Location: ' . base_url('staff/dashboard/pos/items/create?error=Name%20and%20category%20are%20required'));
            return;
        }
        
        $itemId = $this->items->create($data);
        
        // Handle BOM components
        $components = $request->input('components', []);
        if (!empty($components)) {
            $invRepo = new \App\Repositories\InventoryRepository();
            foreach ($components as $component) {
                if (!empty($component['inventory_item_id']) && !empty($component['quantity_per_sale'])) {
                    $invRepo->ensurePosComponent(
                        $itemId,
                        (int)$component['inventory_item_id'],
                        (float)$component['quantity_per_sale'],
                        $component['source_unit'] ?? null,
                        $component['target_unit'] ?? null,
                        (float)($component['conversion_factor'] ?? 1.0)
                    );
                }
            }
        }
        
        header('Location: ' . base_url('staff/dashboard/pos/items?success=Item%20created'));
    }
    
    public function editItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $id = (int)$request->input('id');
        $item = $this->items->find($id);
        
        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/pos/items?error=Item%20not%20found'));
            return;
        }
        
        $categories = $this->items->categories();
        $inventoryItems = (new \App\Repositories\InventoryRepository())->allItems();
        $components = $this->items->getComponents($id);
        
        $this->view('dashboard/pos/items/edit', [
            'item' => $item,
            'categories' => $categories,
            'inventoryItems' => $inventoryItems,
            'components' => $components,
        ]);
    }
    
    public function updateItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager']);
        
        $id = (int)$request->input('id');
        $item = $this->items->find($id);
        
        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/pos/items?error=Item%20not%20found'));
            return;
        }
        
        $data = [
            'category_id' => (int)$request->input('category_id'),
            'name' => trim($request->input('name', '')),
            'price' => (float)$request->input('price', 0),
            'sku' => trim($request->input('sku', '')),
            'tracked' => $request->input('tracked', false),
            'is_inventory_item' => $request->input('is_inventory_item', false),
        ];
        
        if (empty($data['name']) || $data['category_id'] <= 0) {
            header('Location: ' . base_url('staff/dashboard/pos/items/edit?id=' . $id . '&error=Name%20and%20category%20are%20required'));
            return;
        }
        
        $this->items->update($id, $data);
        
        // Handle BOM components - delete existing and recreate
        $db = db();
        $deleteStmt = $db->prepare('DELETE FROM pos_item_components WHERE pos_item_id = :id');
        $deleteStmt->execute(['id' => $id]);
        
        $components = $request->input('components', []);
        if (!empty($components)) {
            $invRepo = new \App\Repositories\InventoryRepository();
            foreach ($components as $component) {
                if (!empty($component['inventory_item_id']) && !empty($component['quantity_per_sale'])) {
                    $invRepo->ensurePosComponent(
                        $id,
                        (int)$component['inventory_item_id'],
                        (float)$component['quantity_per_sale'],
                        $component['source_unit'] ?? null,
                        $component['target_unit'] ?? null,
                        (float)($component['conversion_factor'] ?? 1.0)
                    );
                }
            }
        }
        
        header('Location: ' . base_url('staff/dashboard/pos/items?success=Item%20updated'));
    }
    
    public function deleteItem(Request $request): void
    {
        Auth::requireRoles(['director', 'admin']);
        
        $id = (int)$request->input('id');
        $this->items->delete($id);
        
        header('Location: ' . base_url('staff/dashboard/pos/items?success=Item%20deleted'));
    }
}

