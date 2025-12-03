<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PosItemRepository;
use App\Repositories\PosSaleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\FolioRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use Exception;

class OrderController extends Controller
{
    protected PosItemRepository $items;
    protected PosSaleRepository $sales;
    protected OrderRepository $orders;
    protected FolioRepository $folios;
    protected InventoryService $inventory;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->items = new PosItemRepository();
        $this->sales = new PosSaleRepository();
        $this->orders = new OrderRepository();
        $this->folios = new FolioRepository();
        $this->inventory = new InventoryService();
        $this->notifications = new NotificationService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['guest_cart'] = $_SESSION['guest_cart'] ?? [];
    }

    public function show(Request $request): void
    {
        // Handle reorder request
        $reorderRef = $request->input('reorder_ref');
        if ($reorderRef) {
            $this->handleReorder($reorderRef);
            return;
        }

        // Reuse the Food & Drinks page as the primary order UI
        $reorderSuccess = $request->input('reorder');
        $reorderRef = $request->input('ref');
        
        $this->view('website/food', [
            'categories' => $this->items->categoriesWithItems(),
            'website' => settings('website', []),
            'reorderSuccess' => $reorderSuccess,
            'reorderRef' => $reorderRef,
        ]);
    }

    public function paymentWaiting(Request $request): void
    {
        $orderRef = $request->input('ref');
        $checkoutRequestId = $request->input('checkout_request_id');
        
        if (empty($orderRef)) {
            header('Location: ' . base_url('order?error=Order%20reference%20required'));
            return;
        }
        
        // Find the order/sale
        $sale = $this->sales->findByReference($orderRef);
        if (!$sale) {
            header('Location: ' . base_url('order?error=Order%20not%20found'));
            return;
        }
        
        // Get payment method name for display
        $paymentMethod = $sale['payment_type'] ?? 'digital';
        $paymentMethodName = ucfirst(str_replace('_', ' ', $paymentMethod));
        
        $this->view('website/order/payment-waiting', [
            'orderRef' => $orderRef,
            'checkoutRequestId' => $checkoutRequestId,
            'total' => (float)($sale['total'] ?? 0),
            'paymentStatus' => $sale['payment_status'] ?? 'pending',
            'paymentMethod' => $paymentMethod,
            'paymentMethodName' => $paymentMethodName,
        ]);
    }

    public function checkPaymentStatus(Request $request): void
    {
        header('Content-Type: application/json');
        
        $orderRef = $request->input('ref');
        if (empty($orderRef)) {
            echo json_encode(['ok' => false, 'error' => 'Order reference required']);
            return;
        }
        
        $sale = $this->sales->findByReference($orderRef);
        if (!$sale) {
            echo json_encode(['ok' => false, 'error' => 'Order not found']);
            return;
        }
        
        $paymentStatus = $sale['payment_status'] ?? 'pending';
        $mpesaStatus = $sale['mpesa_status'] ?? null;
        
        // If payment is confirmed, also check if we need to process inventory
        if ($paymentStatus === 'paid' || $mpesaStatus === 'completed') {
            // Check if order entry exists
            $order = $this->orders->findByReference($orderRef);
            if (!$order) {
                // Order entry might not exist yet, try to create it
                try {
                    $this->processOrderAfterPayment($sale);
                } catch (\Exception $e) {
                    error_log('Failed to process order after payment: ' . $e->getMessage());
                }
            }
        }
        
        echo json_encode([
            'ok' => true,
            'payment_status' => $paymentStatus,
            'mpesa_status' => $mpesaStatus,
            'paid' => in_array($paymentStatus, ['paid', 'completed']) || $mpesaStatus === 'completed',
        ]);
    }

    public function confirmPayment(Request $request): void
    {
        $orderRef = $request->input('ref');
        if (empty($orderRef)) {
            if ($request->input('ajax') === '1') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Order reference required']);
                return;
            }
            header('Location: ' . base_url('order/payment-waiting?ref=' . urlencode($orderRef ?? '') . '&error=Order%20reference%20required'));
            return;
        }
        
        $sale = $this->sales->findByReference($orderRef);
        if (!$sale) {
            if ($request->input('ajax') === '1') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Order not found']);
                return;
            }
            header('Location: ' . base_url('order/payment-waiting?ref=' . urlencode($orderRef) . '&error=Order%20not%20found'));
            return;
        }
        
        // Update payment status to paid
        $db = db();
        $stmt = $db->prepare('UPDATE pos_sales SET payment_status = "paid", mpesa_status = "completed" WHERE reference = :ref');
        $stmt->execute(['ref' => $orderRef]);
        
        // Reload sale to get updated data
        $sale = $this->sales->findByReference($orderRef);
        
        // Process order (deduct inventory, create order entry, etc.)
        try {
            $this->processOrderAfterPayment($sale);
        } catch (\Exception $e) {
            error_log('Failed to process order after payment confirmation: ' . $e->getMessage());
        }
        
        if ($request->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'message' => 'Payment confirmed successfully']);
            return;
        }
        
        header('Location: ' . base_url('guest/orders?success=1&ref=' . urlencode($orderRef) . '&payment_status=paid'));
    }

    protected function processOrderAfterPayment(array $sale): void
    {
        $saleId = (int)$sale['id'];
        $saleItems = $this->sales->getItems($saleId);
        
        // Deduct inventory if not already done
        $locations = $this->inventory->locations();
        $locationId = (int)($locations[0]['id'] ?? 0);
        
        foreach ($saleItems as $saleItem) {
            $components = $this->fetchComponents((int)$saleItem['item_id']);
            foreach ($components as $component) {
                $baseQty = $component['quantity_per_sale'] * $saleItem['quantity'];
                if ($baseQty > 0) {
                    $sourceUnit = $component['source_unit'] ?? null;
                    $targetUnit = $component['target_unit'] ?? $component['inventory_unit'] ?? null;
                    $conversionFactor = (float)($component['conversion_factor'] ?? 1.0);
                    $convertedQty = $this->inventory->convertQuantity($baseQty, $sourceUnit, $targetUnit, $conversionFactor);
                    $this->inventory->deductStock((int)$component['inventory_item_id'], $locationId, $convertedQty, 'Guest order #' . $saleId, 'Guest order');
                }
            }
            
            try {
                $this->inventory->updateProductionCost((int)$saleItem['item_id']);
            } catch (\Exception $e) {
                error_log('Failed to update production cost: ' . $e->getMessage());
            }
        }
        
        // Create order entry if it doesn't exist
        $order = $this->orders->findByReference($sale['reference']);
        if (!$order && !empty($saleItems)) {
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
            
            $this->orders->create([
                'reference' => $sale['reference'],
                'order_type' => 'website_delivery',
                'source' => 'website',
                'user_id' => null,
                'reservation_id' => null,
                'customer_name' => $sale['notes'] ?? 'Guest',
                'customer_phone' => $sale['mpesa_phone'] ?? null,
                'customer_email' => null,
                'room_number' => null,
                'service_type' => 'pickup',
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_type' => $sale['payment_type'] ?? 'mpesa',
                'total' => (float)$sale['total'],
                'notes' => $sale['notes'],
                'items' => $orderItems,
            ]);
        }
    }

    protected function handleReorder(string $orderReference): void
    {
        // Get the order by reference
        $order = $this->orders->findByReference(trim($orderReference));
        
        if (!$order) {
            header('Location: ' . base_url('guest/orders?error=Order%20not%20found'));
            return;
        }

        // Verify guest access (optional - can be done if guest is logged in)
        $guest = \App\Support\GuestPortal::user();
        if ($guest) {
            $identifier = $guest['identifier'] ?? '';
            $hasAccess = false;
            if ($identifier) {
                if (str_contains($identifier, '@')) {
                    $hasAccess = strtolower($order['customer_email'] ?? '') === strtolower($identifier);
                } else {
                    $sanitized = preg_replace('/[^0-9]/', '', $identifier);
                    $orderPhone = preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? '');
                    $hasAccess = $sanitized === $orderPhone;
                }
            }
            
            if (!$hasAccess) {
                header('Location: ' . base_url('guest/orders?error=Access%20denied'));
                return;
            }
        }

        // Get order items
        $orderItems = $order['items'] ?? [];
        
        if (empty($orderItems)) {
            header('Location: ' . base_url('guest/orders?error=Order%20has%20no%20items'));
            return;
        }

        // Clear current cart
        $_SESSION['guest_cart'] = [];

        // Add all items from the order to the cart
        $itemsAdded = 0;
        foreach ($orderItems as $item) {
            $posItemId = (int)($item['pos_item_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            
            if ($posItemId > 0 && $quantity > 0) {
                // Verify item still exists and get current price
                $posItem = $this->items->find($posItemId);
                if ($posItem) {
                    // Use current price, not the old price
                    $currentPrice = (float)($posItem['price'] ?? 0);
                    $this->addToCart($posItemId, $quantity, $currentPrice);
                    $itemsAdded++;
                }
            }
        }

        if ($itemsAdded === 0) {
            header('Location: ' . base_url('guest/orders?error=No%20items%20could%20be%20added%20to%20cart'));
            return;
        }

        // Redirect to order page with success message
        header('Location: ' . base_url('order?reorder=success&ref=' . urlencode($orderReference)));
    }

    public function cart(Request $request): void
    {
        header('Content-Type: application/json');
        $action = strtolower((string)$request->input('action', 'get'));

        try {
            if ($action === 'add') {
                $itemId = (int)$request->input('item_id');
                $qty = max(1, (int)$request->input('quantity', 1));
                if ($itemId <= 0) {
                    echo json_encode(['ok' => false, 'error' => 'Invalid item']);
                    return;
                }
                $this->addToCart($itemId, $qty);
                echo json_encode(['ok' => true, 'cart' => $this->cartState()]);
                return;
            }

            if ($action === 'update') {
                $itemId = (int)$request->input('item_id');
                $qty = (int)$request->input('quantity', 1);
                $this->updateCart($itemId, $qty);
                echo json_encode(['ok' => true, 'cart' => $this->cartState()]);
                return;
            }

            if ($action === 'remove') {
                $itemId = (int)$request->input('item_id');
                $this->removeFromCart($itemId);
                echo json_encode(['ok' => true, 'cart' => $this->cartState()]);
                return;
            }

            echo json_encode(['ok' => true, 'cart' => $this->cartState()]);
        } catch (\Throwable $e) {
            error_log('Cart operation failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode(['ok' => false, 'error' => 'Cart operation failed: ' . $e->getMessage()]);
        }
    }

    public function availability(Request $request): void
    {
        header('Content-Type: application/json');
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $locations = $this->inventory->locations();
        $locationId = (int)($locations[0]['id'] ?? 0);
        $result = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            $result[$id] = $this->isAvailableForSale($id, $locationId);
        }
        echo json_encode(['ok' => true, 'availability' => $result]);
    }

    public function checkout(Request $request): void
    {
        $cart = $this->cartState();
        if (empty($cart['lines'])) {
            header('Location: ' . base_url('order?error=' . urlencode('Cart is empty. Please add items to your cart.')));
            return;
        }

        $contact = [
            'name' => trim($request->input('guest_name', 'Guest')),
            'phone' => trim($request->input('guest_phone', '')),
            'email' => trim($request->input('guest_email', '')),
            'notes' => trim($request->input('instructions', '')),
            'service' => $request->input('service_type', 'pickup'),
            'room' => trim($request->input('room_number', '')),
            'payment' => $request->input('payment_method', 'cash'),
        ];

        // Validate required fields
        if (empty($contact['name']) || empty($contact['phone'])) {
            header('Location: ' . base_url('order?error=' . urlencode('Name and phone number are required.')));
            return;
        }

        $saleItems = [];
        foreach ($cart['lines'] as $line) {
            $saleItems[] = [
                'item_id' => (int)$line['id'],
                'quantity' => (int)$line['qty'],
                'price' => (float)$line['price'],
                'line_total' => (float)$line['subtotal'],
            ];
        }
        $total = (float)$cart['total'];

        // Check enabled payment methods for website orders
        $websiteSettings = settings('website', []);
        $enabledPaymentMethods = $websiteSettings['enabled_payment_methods'] ?? ['cash'];
        
        // Backward compatibility: if enable_mpesa_orders exists, convert it
        if (!is_array($enabledPaymentMethods) && !empty($websiteSettings['enable_mpesa_orders'])) {
            $enabledPaymentMethods = ['cash', 'mpesa'];
        } elseif (!is_array($enabledPaymentMethods)) {
            $enabledPaymentMethods = ['cash'];
        }
        
        // Ensure at least cash is enabled
        if (empty($enabledPaymentMethods)) {
            $enabledPaymentMethods = ['cash'];
        }
        
        // Get configured payment gateways
        $paymentGateways = settings('payment_gateways', []);
        
        // Filter: only allow methods that are:
        // 1. In enabledPaymentMethods array, AND
        // 2. Either 'cash' (always available) OR configured and enabled in payment_gateways
        $availableMethods = [];
        foreach ($enabledPaymentMethods as $method) {
            if ($method === 'cash') {
                // Cash is always available
                $availableMethods[] = $method;
            } elseif (isset($paymentGateways[$method]) && !empty($paymentGateways[$method]['enabled'])) {
                // Only allow if gateway is configured and enabled
                $availableMethods[] = $method;
            }
        }
        
        // Ensure at least cash is available
        if (empty($availableMethods)) {
            $availableMethods = ['cash'];
        }
        
        // Validate payment method
        $paymentMethod = $contact['payment'] ?? 'cash';
        if (!in_array($paymentMethod, $availableMethods)) {
            header('Location: ' . base_url('order?error=' . urlencode('Selected payment method is not enabled or configured for website orders.')));
            return;
        }
        
        // Process payment using unified payment processing service
        $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
        
        $mpesaPhone = null;
        $mpesaCheckoutRequestId = null;
        $mpesaMerchantRequestId = null;
        $mpesaStatus = null;
        // All payments start as 'pending' - staff must confirm payment was received
        $paymentStatus = 'pending';
        
        try {
            $reference = 'ORDER-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $paymentOptions = [
                'reference' => $reference,
                'description' => 'Guest Order Payment',
            ];
            
            // Add phone for M-Pesa
            if ($paymentMethod === 'mpesa') {
                $mpesaPhone = trim($contact['phone'] ?? '');
                if (empty($mpesaPhone)) {
                    header('Location: ' . base_url('order?error=' . urlencode('Phone number is required for M-Pesa payment.')));
                    return;
                }
                $paymentOptions['phone'] = $mpesaPhone;
            }
            
            $paymentResult = $paymentProcessor->processPayment($paymentMethod, $total, $paymentOptions);
            
            $mpesaPhone = $paymentResult['mpesa_phone'] ?? $mpesaPhone;
            $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
            $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;
            $mpesaStatus = $paymentResult['mpesa_status'] ?? null;
            
            // Log payment processing result for debugging
            error_log('Payment processing result for ' . $paymentMethod . ': ' . json_encode([
                'payment_status' => $paymentResult['payment_status'] ?? 'unknown',
                'mpesa_status' => $mpesaStatus,
                'checkout_request_id' => $mpesaCheckoutRequestId,
                'reference' => $reference,
            ]));
            
            // Determine payment status based on payment method and result
            if ($paymentMethod === 'cash') {
                // For website orders with cash/pay on delivery, payment is pending until staff verifies
                // Staff will confirm payment when order is delivered/picked up
                $paymentStatus = 'pending';
            } elseif ($paymentMethod === 'mpesa') {
                $paymentStatus = $paymentResult['payment_status'] ?? 'pending';
                // If M-Pesa status is completed, payment is paid
                if ($mpesaStatus === 'completed') {
                    $paymentStatus = 'paid';
                }
            } else {
                // For other payment methods, default to pending unless explicitly confirmed
                // Only mark as paid if payment processor explicitly confirms it
                $paymentStatus = $paymentResult['payment_status'] ?? 'pending';
                // If payment processor returns 'paid' or 'completed', use that
                if (isset($paymentResult['payment_status']) && in_array($paymentResult['payment_status'], ['paid', 'completed'])) {
                    $paymentStatus = 'paid';
                }
            }
            
            // For website orders, if M-Pesa payment is pending, don't complete the order
            // Only allow cash on delivery/pickup to be completed immediately
            if ($paymentMethod === 'mpesa' && $paymentStatus === 'pending') {
                // Order will be created with pending status, not completed
                // Staff will need to confirm payment before processing
            }
        } catch (Exception $e) {
            // Log the error
            error_log('Payment processing failed: ' . $e->getMessage());
            // Redirect with error message instead of showing error page
            header('Location: ' . base_url('order?error=' . urlencode('Payment processing failed. Please try again or contact support.')));
            return;
        }

        // Create the sale/order first
        try {
            $saleId = $this->sales->create([
                'user_id' => null,
                'till_id' => null,
                'payment_type' => $paymentMethod,
                'total' => $total,
                'notes' => sprintf('Guest order (%s) %s', $contact['service'], $contact['notes']),
                'reservation_id' => null,
                'mpesa_phone' => $mpesaPhone,
                'mpesa_checkout_request_id' => $mpesaCheckoutRequestId,
                'mpesa_merchant_request_id' => $mpesaMerchantRequestId,
                'mpesa_status' => $mpesaStatus,
                'payment_status' => $paymentStatus,
            ], $saleItems);
            
            // Get the sale reference for redirect
            $sale = $this->sales->findById($saleId);
            $reference = $sale['reference'] ?? $reference;
            
            error_log('Order created successfully: Sale ID ' . $saleId . ', Reference: ' . $reference . ', Payment Method: ' . $paymentMethod . ', Payment Status: ' . $paymentStatus);
            $sale = $this->sales->findById($saleId);
            $reference = $sale['reference'] ?? $reference;
        } catch (\Exception $e) {
            error_log('Failed to create sale: ' . $e->getMessage());
            header('Location: ' . base_url('order?error=' . urlencode('Failed to create order. Please try again.')));
            return;
        }

        // Only deduct inventory if payment is confirmed (paid)
        // For pending M-Pesa payments, wait for payment confirmation before deducting inventory
        if ($paymentStatus === 'paid') {
            $locations = $this->inventory->locations();
            $locationId = (int)($locations[0]['id'] ?? 0);
            foreach ($saleItems as $line) {
                $components = $this->fetchComponents($line['item_id']);
                foreach ($components as $component) {
                    // Calculate base quantity needed
                    $baseQty = $component['quantity_per_sale'] * $line['quantity'];
                    
                    if ($baseQty > 0) {
                        // Apply unit conversion if needed
                        $sourceUnit = $component['source_unit'] ?? null;
                        $targetUnit = $component['target_unit'] ?? $component['inventory_unit'] ?? null;
                        $conversionFactor = (float)($component['conversion_factor'] ?? 1.0);
                        
                        // Convert quantity to inventory unit
                        $convertedQty = $this->inventory->convertQuantity($baseQty, $sourceUnit, $targetUnit, $conversionFactor);
                        
                        $this->inventory->deductStock((int)$component['inventory_item_id'], $locationId, $convertedQty, 'Guest order #' . $saleId, 'Guest order');
                    }
                }
                
                // Update production cost for this POS item (in case ingredient costs changed)
                try {
                    $this->inventory->updateProductionCost((int)$line['item_id']);
                } catch (\Exception $e) {
                    // Log but don't fail the order
                    error_log('Failed to update production cost for POS item ' . $line['item_id'] . ': ' . $e->getMessage());
                }
            }
        }

        // Create corresponding order entry for order management system
        try {
            $sale = $this->sales->findById($saleId);
            $saleItems = $this->sales->getItems($saleId);

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
            // All orders should follow the workflow: pending → confirmed → preparing → ready → delivered → completed
            // For website orders: start at 'pending' until payment is verified by staff
            // Staff will verify payment and progress orders through the workflow
            if ($paymentStatus === 'paid') {
                $orderStatus = 'confirmed'; // Payment confirmed, ready for kitchen to start preparing
            } else {
                // For cash/pay on delivery, M-Pesa pending, or any unpaid status - start as pending
                // Staff must verify payment before confirming the order
                $orderStatus = 'pending';
            }

            // Normalize phone number for consistent storage and retrieval
            $normalizedPhone = preg_replace('/[^0-9]/', '', $contact['phone']);
            $normalizedEmail = !empty($contact['email']) ? strtolower(trim($contact['email'])) : null;
            
            $orderId = $this->orders->create([
                'reference' => $sale['reference'] ?? $reference,
                'order_type' => 'website_delivery',
                'source' => 'website',
                'user_id' => null,
                'reservation_id' => null,
                'customer_name' => $contact['name'],
                'customer_phone' => $normalizedPhone ?: $contact['phone'], // Store normalized version
                'customer_email' => $normalizedEmail,
                'room_number' => $contact['room'] ?? null,
                'service_type' => in_array($contact['service'], ['delivery', 'room_service', 'eat_in', 'pickup']) 
                    ? $contact['service'] 
                    : 'pickup',
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_type' => $paymentMethod,
                'total' => $total,
                'notes' => $contact['notes'],
                'items' => $orderItems,
            ]);

            // Send notifications for new order (both pending and confirmed orders need staff attention)
            $orderData = $this->orders->findById($orderId);
            if ($orderData) {
                $orderRef = $orderData['reference'] ?? $reference;
                $statusMessage = $orderStatus === 'pending' 
                    ? 'New order #%s received. Payment pending. Total: KES %s'
                    : 'New order #%s received. Payment confirmed. Total: KES %s';
                
                // Check if order contains items that require kitchen preparation
                $requiresKitchen = $this->orderRequiresKitchen($cart['lines']);
                
                // Only notify kitchen if order contains items that need preparation
                if ($requiresKitchen) {
                    $this->notifications->notifyRole('kitchen', 'New Order', 
                        sprintf($statusMessage, $orderRef, number_format($total, 2)),
                        ['order_id' => $orderId, 'reference' => $orderRef]
                    );
                }
                
                // Notify service agents
                $this->notifications->notifyRole('service_agent', 'New Order', 
                    sprintf('New order #%s received. %s', $orderRef, 
                        $contact['service'] === 'room_service' ? 'Room service order.' : 'Customer order.'),
                    ['order_id' => $orderId, 'reference' => $orderRef]
                );
                
                // Notify operations manager
                $this->notifications->notifyRole('operation_manager', 'New Order', 
                    sprintf('New order #%s created via website. Status: %s', $orderRef, ucfirst($orderStatus)),
                    ['order_id' => $orderId, 'reference' => $orderRef]
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the sale
            error_log('Failed to create order entry for website sale #' . $saleId . ': ' . $e->getMessage());
        }

        $payload = [
            'sale_id' => $saleId,
            'total' => $total,
            'contact' => $contact,
            'items' => $cart['lines'],
        ];
        
        // Only notify kitchen if order contains items that need preparation
        $requiresKitchen = $this->orderRequiresKitchen($cart['lines']);
        if ($requiresKitchen) {
            $this->notifications->notifyRole('kitchen', 'New Guest Order', 'Guest placed an order. Prep required.', $payload);
        }
        
        $this->notifications->notifyRole('cashier', 'New Guest Order', 'Guest placed an order. Awaiting payment.', $payload);

            // Normalize phone number for consistent matching
        $normalizedPhone = preg_replace('/[^0-9]/', '', $contact['phone']);
        $normalizedEmail = !empty($contact['email']) ? strtolower(trim($contact['email'])) : null;
        
        // Auto-login guest if they provided email or phone, so orders appear in their account
        if (!empty($contact['email']) || !empty($normalizedPhone)) {
            $identifier = !empty($contact['email']) ? strtolower(trim($contact['email'])) : $normalizedPhone;
            \App\Support\GuestPortal::login([
                'guest_name' => $contact['name'],
                'guest_email' => $contact['email'],
                'guest_phone' => $contact['phone'],
                'identifier' => $identifier,
                'identifier_type' => !empty($contact['email']) ? 'email' : 'phone',
            ]);
        }
        
        // For "pay on delivery" (cash) orders, only add to folio if guest has an active reservation
        // This prevents creating folios for every standalone order
        if ($paymentMethod === 'cash' && ($paymentStatus === 'pending' || $paymentStatus === 'unpaid')) {
            try {
                // Only add to folio if guest has an active reservation (checked in)
                $db = db();
                $reservation = null;
                
                if ($normalizedEmail || $normalizedPhone) {
                    // Check if guest has an active reservation (checked in)
                    $reservationSql = "
                        SELECT id, reference, guest_name, guest_email, guest_phone 
                        FROM reservations 
                        WHERE status = 'checked_in' 
                        AND (
                            " . ($normalizedEmail ? "LOWER(guest_email) = :email" : "1=0") . "
                            OR " . ($normalizedPhone ? "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guest_phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = :phone" : "1=0") . "
                        )
                        ORDER BY check_in DESC
                        LIMIT 1
                    ";
                    $reservationParams = [];
                    if ($normalizedEmail) $reservationParams['email'] = $normalizedEmail;
                    if ($normalizedPhone) $reservationParams['phone'] = $normalizedPhone;
                    
                    $reservationStmt = $db->prepare($reservationSql);
                    $reservationStmt->execute($reservationParams);
                    $reservation = $reservationStmt->fetch();
                }
                
                if ($reservation) {
                    // Guest has an active reservation - find or create folio for the reservation
                    $folio = $this->folios->findByReservation((int)$reservation['id']);
                    
                    if (!$folio) {
                        // Create folio for the reservation if it doesn't exist
                        $folioId = $this->folios->create(
                            (int)$reservation['id'],
                            $normalizedEmail,
                            $normalizedPhone,
                            $reservation['guest_name'] ?? $contact['name']
                        );
                        $stmt = $db->prepare('SELECT * FROM folios WHERE id = :id');
                        $stmt->execute(['id' => $folioId]);
                        $folio = $stmt->fetch();
                    }
                    
                    if ($folio && $folio['status'] === 'open') {
                        // Add order as a charge entry to the folio
                        $orderDescription = sprintf('Order #%s - %s', $reference, $contact['service'] === 'delivery' ? 'Delivery' : 'Pickup');
                        if (!empty($contact['notes'])) {
                            $orderDescription .= ' - ' . $contact['notes'];
                        }
                        $this->folios->addEntry(
                            (int)$folio['id'],
                            $orderDescription,
                            $total,
                            'charge',
                            'order'
                        );
                        error_log('Added order ' . $reference . ' to folio ' . $folio['id'] . ' for checked-in guest');
                    }
                } else {
                    // No active reservation - don't create folio for standalone orders
                    // These orders will be tracked separately and don't need to show in Outstanding Balance
                    error_log('Order ' . $reference . ' not added to folio - guest has no active reservation');
                }
            } catch (\Exception $e) {
                // Log error but don't fail the order
                error_log('Failed to add order to folio: ' . $e->getMessage());
            }
        }
        
        // Clear cart only after successful order creation
        $_SESSION['guest_cart'] = [];
        
        // For M-Pesa payments, always redirect to payment waiting page unless payment is already confirmed
        // This ensures users see the payment confirmation page and can wait for M-Pesa callback
        error_log('Checkout redirect logic: paymentMethod=' . $paymentMethod . ', paymentStatus=' . $paymentStatus . ', mpesaStatus=' . ($mpesaStatus ?? 'null') . ', reference=' . $reference);
        
        if ($paymentMethod === 'mpesa') {
            // Only redirect to success if payment is already confirmed (shouldn't happen, but handle it)
            if ($paymentStatus === 'paid' && $mpesaStatus === 'completed') {
                error_log('M-Pesa payment already confirmed, redirecting to orders page');
                header('Location: ' . base_url('guest/orders?success=1&ref=' . urlencode($reference) . '&payment_status=paid'));
            } else {
                // For M-Pesa, always go to payment waiting page to wait for confirmation
                error_log('M-Pesa payment pending, redirecting to payment waiting page. Reference: ' . $reference);
                header('Location: ' . base_url('order/payment-waiting?ref=' . urlencode($reference) . '&checkout_request_id=' . urlencode($mpesaCheckoutRequestId ?? '')));
            }
            exit; // Ensure redirect happens
        } elseif ($paymentMethod !== 'cash' && $paymentStatus === 'pending') {
            // For other digital payment methods with pending status, redirect to payment waiting page
            error_log('Digital payment pending, redirecting to payment waiting page. Reference: ' . $reference);
            header('Location: ' . base_url('order/payment-waiting?ref=' . urlencode($reference) . '&checkout_request_id=' . urlencode($mpesaCheckoutRequestId ?? '')));
            exit; // Ensure redirect happens
        } else {
            // For paid orders (cash, confirmed digital payments), redirect to success page
            error_log('Payment confirmed, redirecting to orders page. Reference: ' . $reference);
            header('Location: ' . base_url('guest/orders?success=1&ref=' . urlencode($reference) . '&payment_status=' . urlencode($paymentStatus)));
            exit; // Ensure redirect happens
        }
    }

    protected function isAvailableForSale(int $posItemId, int $locationId): bool
    {
        // If item has no components or is not tracked, assume available
        $db = db();
        $tracked = (int)$db->query('SELECT tracked FROM pos_items WHERE id = ' . (int)$posItemId)->fetchColumn();
        if ($tracked === 0) {
            return true;
        }
        $components = $this->fetchComponents($posItemId);
        if (!$components) {
            return true;
        }
        $inv = new \App\Repositories\InventoryRepository();
        foreach ($components as $c) {
            $need = (float)$c['quantity_per_sale'];
            if ($need <= 0) {
                continue;
            }
            $have = $inv->level((int)$c['inventory_item_id'], $locationId);
            if ($have < $need) {
                return false;
            }
        }
        return true;
    }

    protected function fetchComponents(int $posItemId): array
    {
        $db = db();
        $stmt = $db->prepare('SELECT inventory_item_id, quantity_per_sale FROM pos_item_components WHERE pos_item_id = :id');
        $stmt->execute(['id' => $posItemId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Check if an order contains items that require kitchen preparation
     * Kitchen items are typically: Breakfast, Lunch, Dinner, Snacks, Specials
     * Non-kitchen items: Soft Drinks, Alcohol, Beverages, etc.
     */
    protected function orderRequiresKitchen(array $cartLines): bool
    {
        if (empty($cartLines)) {
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
            return (int)($line['id'] ?? 0);
        }, $cartLines);
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

    protected function addToCart(int $itemId, int $qty, ?float $price = null): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }
        
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $item = $this->items->find($itemId);
        if (!$item) {
            throw new \RuntimeException('Item not found: ' . $itemId);
        }
        
        $key = (string)$itemId;
        if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        if (!isset($_SESSION['guest_cart'][$key])) {
            // Use provided price or item's current price
            $itemPrice = $price !== null ? $price : (float)($item['price'] ?? 0);
            $_SESSION['guest_cart'][$key] = [
                'id' => $itemId,
                'name' => $item['name'] ?? 'Unknown Item',
                'price' => $itemPrice,
                'qty' => 0
            ];
        }
        $_SESSION['guest_cart'][$key]['qty'] += $qty;
    }

    protected function updateCart(int $itemId, int $qty): void
    {
        $key = (string)$itemId;
        if (!isset($_SESSION['guest_cart'][$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($_SESSION['guest_cart'][$key]);
            return;
        }
        $_SESSION['guest_cart'][$key]['qty'] = $qty;
    }

    protected function removeFromCart(int $itemId): void
    {
        $key = (string)$itemId;
        unset($_SESSION['guest_cart'][$key]);
    }

    protected function cartState(): array
    {
        $lines = [];
        $total = 0.0;
        if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        foreach ($_SESSION['guest_cart'] as $line) {
            if (!isset($line['id']) || !isset($line['qty']) || !isset($line['price'])) {
                continue; // Skip invalid cart entries
            }
            $subtotal = (float)$line['qty'] * (float)$line['price'];
            $lines[] = [
                'id' => (int)$line['id'],
                'name' => $line['name'] ?? 'Unknown Item',
                'qty' => (int)$line['qty'],
                'price' => (float)$line['price'],
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
        return ['lines' => $lines, 'total' => round($total, 2)];
    }
}


