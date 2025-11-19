<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\PosItemRepository;
use App\Repositories\PosSaleRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;

class OrderController extends Controller
{
    protected PosItemRepository $items;
    protected PosSaleRepository $sales;
    protected InventoryService $inventory;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->items = new PosItemRepository();
        $this->sales = new PosSaleRepository();
        $this->inventory = new InventoryService();
        $this->notifications = new NotificationService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['guest_cart'] = $_SESSION['guest_cart'] ?? [];
    }

    public function show(Request $request): void
    {
        // Reuse the Food & Drinks page as the primary order UI
        $this->view('website/food', [
            'categories' => $this->items->categoriesWithItems(),
            'website' => settings('website', []),
        ]);
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
            echo json_encode(['ok' => false, 'error' => 'Cart operation failed']);
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
            http_response_code(400);
            echo 'Cart empty';
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

        // Process payment using unified payment processing service
        $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
        $paymentMethod = $contact['payment'] ?? 'cash';
        
        $mpesaPhone = null;
        $mpesaCheckoutRequestId = null;
        $mpesaMerchantRequestId = null;
        $mpesaStatus = null;
        $paymentStatus = 'paid';
        
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
                    http_response_code(422);
                    echo 'Phone number is required for M-Pesa payment.';
                    return;
                }
                $paymentOptions['phone'] = $mpesaPhone;
            }
            
            $paymentResult = $paymentProcessor->processPayment($paymentMethod, $total, $paymentOptions);
            
            $mpesaPhone = $paymentResult['mpesa_phone'] ?? null;
            $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
            $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;
            $mpesaStatus = $paymentResult['mpesa_status'] ?? null;
            $paymentStatus = $paymentResult['payment_status'] ?? 'paid';
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Payment processing failed: ' . $e->getMessage();
            return;
        }

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

        // Deduct inventory using recipe components, default first location
        $locations = $this->inventory->locations();
        $locationId = (int)($locations[0]['id'] ?? 0);
        foreach ($saleItems as $line) {
            $components = $this->fetchComponents($line['item_id']);
            foreach ($components as $component) {
                $qty = $component['quantity_per_sale'] * $line['quantity'];
                if ($qty > 0) {
                    $this->inventory->deductStock((int)$component['inventory_item_id'], $locationId, $qty, 'Guest order #' . $saleId, 'Guest order');
                }
            }
        }

        $payload = [
            'sale_id' => $saleId,
            'total' => $total,
            'contact' => $contact,
            'items' => $cart['lines'],
        ];
        $this->notifications->notifyRole('kitchen', 'New Guest Order', 'Guest placed an order. Prep required.', $payload);
        $this->notifications->notifyRole('cashier', 'New Guest Order', 'Guest placed an order. Awaiting payment.', $payload);

        $_SESSION['guest_cart'] = [];
        header('Location: ' . base_url('order?success=1'));
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

    protected function addToCart(int $itemId, int $qty): void
    {
        if ($qty <= 0) return;
        $item = $this->items->find($itemId);
        if (!$item) return;
        $key = (string)$itemId;
        if (!isset($_SESSION['guest_cart'][$key])) {
            $_SESSION['guest_cart'][$key] = ['id' => $itemId, 'name' => $item['name'], 'price' => (float)$item['price'], 'qty' => 0];
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
        foreach ($_SESSION['guest_cart'] as $line) {
            $subtotal = $line['qty'] * $line['price'];
            $lines[] = [
                'id' => $line['id'],
                'name' => $line['name'],
                'qty' => $line['qty'],
                'price' => $line['price'],
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
        return ['lines' => $lines, 'total' => $total];
    }
}


