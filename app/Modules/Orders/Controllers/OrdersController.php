<?php

namespace App\Modules\Orders\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\OrderRepository;
use App\Services\Notifications\NotificationService;
use App\Support\Auth;
use Exception;

class OrdersController extends Controller
{
    protected OrderRepository $orders;
    protected NotificationService $notifications;

    public function __construct()
    {
        $this->orders = new OrderRepository();
        $this->notifications = new NotificationService();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        $filters = [
            'status' => $request->input('status'),
            'order_type' => $request->input('order_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $orders = $this->orders->all($filters, 50);
        $statusCounts = $this->orders->getCountsByStatus();

        // Get sale data for each order to check M-Pesa status
        $saleRepo = new \App\Repositories\PosSaleRepository();
        foreach ($orders as &$order) {
            if (!empty($order['reference'])) {
                $sale = $saleRepo->findByReference($order['reference']);
                $order['sale'] = $sale;
            }
        }
        unset($order); // Break reference

        // If AJAX request, return JSON with orders data
        if ($request->input('ajax') === '1') {
            header('Content-Type: application/json');
            
            // Render orders list HTML
            ob_start();
            if (empty($orders)) {
                echo '<div class="empty-state">
                    <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 12l2 2 4-4"></path>
                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                    </svg>
                    <h3>No orders found</h3>
                    <p>There are no orders matching your filters.</p>
                </div>';
            } else {
                echo '<div class="orders-list">';
                foreach ($orders as $order) {
                    include view_path('dashboard/orders/_order-card.php');
                }
                echo '</div>';
            }
            $ordersHtml = ob_get_clean();
            
            // Render status counts
            ob_start();
            if (!empty($statusCounts)) {
                echo '<div class="status-counts">';
                foreach ($statusCounts as $status => $count) {
                    echo '<div class="status-badge status-' . htmlspecialchars($status) . '">
                        <span class="status-label">' . ucfirst($status) . '</span>
                        <span class="status-count">' . $count . '</span>
                    </div>';
                }
                echo '</div>';
            }
            $statusCountsHtml = ob_get_clean();
            
            echo json_encode([
                'success' => true,
                'orders_html' => $ordersHtml,
                'status_counts_html' => $statusCountsHtml,
            ]);
            return;
        }

        $this->view('dashboard/orders/index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function my(Request $request): void
    {
        Auth::requireRoles(['service_agent', 'kitchen', 'cashier']);

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        $filters = [
            'assigned_staff_id' => $userId,
            'status' => $request->input('status'),
            'order_type' => $request->input('order_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $orders = $this->orders->all($filters, 50);
        $statusCounts = $this->orders->getCountsByStatus(['assigned_staff_id' => $userId]);

        $this->view('dashboard/orders/my', [
            'orders' => $orders,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function updates(Request $request): void
    {
        Auth::requireRoles(['service_agent', 'kitchen', 'cashier']);

        // Show recent order updates (similar to my orders but focused on recent changes)
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        $filters = [
            'assigned_staff_id' => $userId,
            'status' => $request->input('status'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        // Get orders updated in last 24 hours
        $filters['updated_since'] = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $orders = $this->orders->all($filters, 50);

        $this->view('dashboard/orders/updates', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        $orderId = (int)$request->input('id');
        $order = $this->orders->findById($orderId);

        if (!$order) {
            header('Location: ' . base_url('staff/dashboard/orders?error=Order%20not%20found'));
            return;
        }

        // Get associated sale data to check M-Pesa status and sync payment status
        $sale = null;
        if (!empty($order['reference'])) {
            $saleRepo = new \App\Repositories\PosSaleRepository();
            $sale = $saleRepo->findByReference($order['reference']);
            
            // Sync payment status from POS sale to order if sale exists and has different status
            if ($sale) {
                $salePaymentStatus = $sale['payment_status'] ?? 'pending';
                $orderPaymentStatus = $order['payment_status'] ?? 'pending';
                
                // If sale payment status is paid/completed but order shows pending, sync it
                if (in_array($salePaymentStatus, ['paid', 'completed']) && in_array($orderPaymentStatus, ['pending', 'unpaid'])) {
                    $db = db();
                    $stmt = $db->prepare('UPDATE orders SET payment_status = :status WHERE id = :id');
                    $stmt->execute([
                        'status' => $salePaymentStatus === 'completed' ? 'paid' : $salePaymentStatus,
                        'id' => $orderId
                    ]);
                    // Reload order to get updated payment status
                    $order = $this->orders->findById($orderId);
                }
            }
        }

        $this->view('dashboard/orders/show', [
            'order' => $order,
            'sale' => $sale,
        ]);
    }

    public function updateStatus(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen']);

        header('Content-Type: application/json');

        $orderId = (int)$request->input('order_id');
        $status = trim($request->input('status', ''));
        $notes = trim($request->input('notes', ''));

        if (!$orderId || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
            return;
        }

        $order = $this->orders->findById($orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        try {
            $this->orders->updateStatus($orderId, $status, $userId, $notes);
            
            // Note: Order confirmation and payment confirmation are separate actions
            // Staff can confirm an order (start preparing) even if payment is still pending
            // Payment should be confirmed separately using the "Confirm Payment" action
            
            // If order is completed, sync payment status from POS sale if payment was received
            if ($status === 'completed' && !empty($order['reference'])) {
                $saleRepo = new \App\Repositories\PosSaleRepository();
                $sale = $saleRepo->findByReference($order['reference']);
                if ($sale) {
                    $salePaymentStatus = $sale['payment_status'] ?? 'pending';
                    $orderPaymentStatus = $order['payment_status'] ?? 'pending';
                    
                    // If sale shows paid/completed but order doesn't, sync it
                    if (in_array($salePaymentStatus, ['paid', 'completed']) && in_array($orderPaymentStatus, ['pending', 'unpaid'])) {
                        $db = db();
                        $stmt = $db->prepare('UPDATE orders SET payment_status = :status WHERE id = :id');
                        $stmt->execute([
                            'status' => $salePaymentStatus === 'completed' ? 'paid' : $salePaymentStatus,
                            'id' => $orderId
                        ]);
                    }
                }
            }

            // Send notifications based on status
            $this->sendStatusNotifications($order, $status, $user);

            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $this->orders->findById($orderId),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function assignStaff(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'service_agent']);

        header('Content-Type: application/json');

        $orderId = (int)$request->input('order_id');
        $staffId = (int)$request->input('staff_id');

        if (!$orderId || !$staffId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID and staff ID are required']);
            return;
        }

        try {
            $this->orders->assignStaff($orderId, $staffId);
            echo json_encode(['success' => true, 'message' => 'Staff assigned successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function confirmPayment(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'finance_manager']);

        $orderId = (int)$request->input('order_id');
        $orderRef = trim($request->input('reference', ''));
        
        if (!$orderId && !$orderRef) {
            header('Location: ' . base_url('staff/dashboard/orders?error=Order%20ID%20or%20reference%20required'));
            return;
        }
        
        $order = $orderId ? $this->orders->findById($orderId) : $this->orders->findByReference($orderRef);
        if (!$order) {
            header('Location: ' . base_url('staff/dashboard/orders?error=Order%20not%20found'));
            return;
        }
        
        // Update order payment status
        $db = db();
        $stmt = $db->prepare('UPDATE orders SET payment_status = "paid" WHERE id = :id');
        $stmt->execute(['id' => (int)$order['id']]);
        
        // Also update the corresponding POS sale if it exists
        $saleRepo = new \App\Repositories\PosSaleRepository();
        $sale = $saleRepo->findByReference($order['reference']);
        if ($sale) {
            $saleStmt = $db->prepare('UPDATE pos_sales SET payment_status = "paid", mpesa_status = "completed" WHERE reference = :ref');
            $saleStmt->execute(['ref' => $order['reference']]);
            
            // Reload sale to get updated data
            $sale = $saleRepo->findByReference($order['reference']);
            
            // Process inventory deduction if not already done
            try {
                $saleId = (int)$sale['id'];
                $saleItems = $saleRepo->getItems($saleId);
                
                $inventoryService = new \App\Services\Inventory\InventoryService();
                $locations = $inventoryService->locations();
                $locationId = (int)($locations[0]['id'] ?? 0);
                
                $posItemRepo = new \App\Repositories\PosItemRepository();
                $invRepo = new \App\Repositories\InventoryRepository();
                
                foreach ($saleItems as $saleItem) {
                    $components = $invRepo->getPosComponents((int)$saleItem['item_id']);
                    foreach ($components as $component) {
                        $baseQty = (float)($component['quantity_per_sale'] ?? 0) * (float)$saleItem['quantity'];
                        if ($baseQty > 0) {
                            $sourceUnit = $component['source_unit'] ?? null;
                            $targetUnit = $component['target_unit'] ?? $component['inventory_unit'] ?? null;
                            $conversionFactor = (float)($component['conversion_factor'] ?? 1.0);
                            $convertedQty = $inventoryService->convertQuantity($baseQty, $sourceUnit, $targetUnit, $conversionFactor);
                            $inventoryService->deductStock((int)$component['inventory_item_id'], $locationId, $convertedQty, 'Order #' . $order['reference'], 'Order payment confirmed');
                        }
                    }
                    
                    try {
                        $invRepo->updateProductionCost((int)$saleItem['item_id']);
                    } catch (\Exception $e) {
                        error_log('Failed to update production cost: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                error_log('Failed to process inventory after admin payment confirmation: ' . $e->getMessage());
            }
        }
        
        header('Location: ' . base_url('staff/dashboard/orders/show?id=' . (int)$order['id'] . '&success=Payment%20confirmed'));
    }

    public function requestPayment(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'finance_manager']);

        $orderId = (int)$request->input('order_id');
        $orderRef = trim($request->input('reference', ''));
        
        if (!$orderId && !$orderRef) {
            header('Location: ' . base_url('staff/dashboard/orders?error=Order%20ID%20or%20reference%20required'));
            return;
        }
        
        $order = $orderId ? $this->orders->findById($orderId) : $this->orders->findByReference($orderRef);
        if (!$order) {
            header('Location: ' . base_url('staff/dashboard/orders?error=Order%20not%20found'));
            return;
        }

        // Generate payment link (full URL)
        $paymentLink = base_url('guest/order/pay?ref=' . urlencode($order['reference']));
        
        // Ensure it's a full URL (check if base_url returns relative path)
        if (!preg_match('/^https?:\/\//', $paymentLink)) {
            // If base_url doesn't include protocol, construct full URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $paymentLink = $protocol . '://' . $host . $paymentLink;
        }
        
        // Get customer contact information
        $customerEmail = $order['customer_email'] ?? null;
        $customerPhone = $order['customer_phone'] ?? null;
        $customerName = $order['customer_name'] ?? 'Customer';
        $orderTotal = (float)($order['total'] ?? 0);
        $orderRef = $order['reference'];
        
        // Prepare SMS message
        $brandName = settings('branding.name', 'Hotela');
        $smsMessage = "Dear {$customerName}, payment pending for Order #{$orderRef} (KES " . number_format($orderTotal, 2) . "). Pay here: {$paymentLink} - {$brandName}";
        
        // Send notifications
        $emailSent = false;
        $smsSent = false;
        $errors = [];
        
        // Send email if customer email is available
        if ($customerEmail) {
            try {
                $emailService = new \App\Services\Email\EmailService();
                $emailSent = $emailService->sendPaymentRequest(
                    $customerEmail,
                    $customerName,
                    $orderRef,
                    $orderTotal,
                    $paymentLink,
                    $customerName
                );
                if (!$emailSent) {
                    $errors[] = 'Email sending failed';
                }
            } catch (\Exception $e) {
                error_log('Email sending error: ' . $e->getMessage());
                $errors[] = 'Email sending error: ' . $e->getMessage();
            }
        }
        
        // Send SMS if customer phone is available
        if ($customerPhone) {
            try {
                $smsService = new \App\Services\Sms\SmsService();
                $smsSent = $smsService->send($customerPhone, $smsMessage);
                if (!$smsSent) {
                    $errors[] = 'SMS sending failed';
                }
            } catch (\Exception $e) {
                error_log('SMS sending error: ' . $e->getMessage());
                $errors[] = 'SMS sending error: ' . $e->getMessage();
            }
        }
        
        // Build success message
        $successParts = [];
        if ($emailSent) {
            $successParts[] = 'Email sent';
        }
        if ($smsSent) {
            $successParts[] = 'SMS sent';
        }
        if (empty($successParts)) {
            if (!$customerEmail && !$customerPhone) {
                $successMessage = 'Payment link generated (no contact info available)';
            } else {
                $successMessage = 'Payment link generated (sending failed - check settings)';
            }
        } else {
            $successMessage = 'Payment request sent via ' . implode(' and ', $successParts);
        }
        
        // If there were errors but at least one succeeded, append error info
        if (!empty($errors) && ($emailSent || $smsSent)) {
            $successMessage .= ' (' . implode(', ', $errors) . ')';
        }
        
        header('Location: ' . base_url('staff/dashboard/orders/show?id=' . (int)$order['id'] . '&payment_link=' . urlencode($paymentLink) . '&success=' . urlencode($successMessage)));
    }

    public function addComment(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        header('Content-Type: application/json');

        $orderId = (int)$request->input('order_id');
        $comment = trim($request->input('comment', ''));
        $visibility = trim($request->input('visibility', 'all'));

        if (!$orderId || empty($comment)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID and comment are required']);
            return;
        }

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        try {
            $this->orders->addComment($orderId, $userId, $comment, $visibility);
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent']);

        header('Content-Type: application/json');

        $orderId = (int)$request->input('order_id');
        $reason = trim($request->input('reason', ''));

        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
            return;
        }

        if (empty($reason)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cancellation reason is required']);
            return;
        }

        $order = $this->orders->findById($orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        try {
            // Handle cancellation: restore inventory and process refunds if needed
            $this->handleOrderCancellation($order, $userId, $reason);
            
            $this->orders->updateStatus($orderId, 'cancelled', $userId, $reason);

            // Notify relevant roles about cancellation
            $this->notifications->notifyRole('operation_manager', 'Order Cancelled', 
                sprintf('Order #%s has been cancelled. Reason: %s', $order['reference'], $reason),
                ['order_id' => $orderId, 'reference' => $order['reference']]
            );
            $this->notifications->notifyRole('kitchen', 'Order Cancelled', 
                sprintf('Order #%s has been cancelled. Reason: %s', $order['reference'], $reason),
                ['order_id' => $orderId, 'reference' => $order['reference']]
            );
            $this->notifications->notifyRole('service_agent', 'Order Cancelled', 
                sprintf('Order #%s has been cancelled. Reason: %s', $order['reference'], $reason),
                ['order_id' => $orderId, 'reference' => $order['reference']]
            );
            $this->notifications->notifyRole('finance_manager', 'Order Cancelled', 
                sprintf('Order #%s has been cancelled. Reason: %s', $order['reference'], $reason),
                ['order_id' => $orderId, 'reference' => $order['reference']]
            );

            if ($order['customer_email']) {
                // TODO: Send email notification to customer
            }

            echo json_encode([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order' => $this->orders->findById($orderId),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Send notifications based on order status change
     */
    protected function sendStatusNotifications(array $order, string $newStatus, array $user): void
    {
        $orderRef = $order['reference'];
        $userName = $user['name'] ?? 'System';

        switch ($newStatus) {
            case 'pending':
                // Notify kitchen and service agents about new pending orders
                $this->notifications->notifyRole('kitchen', 'New Order', 
                    sprintf('New order #%s received. Total: KES %s', $orderRef, number_format((float)($order['total'] ?? 0), 2)),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                $this->notifications->notifyRole('service_agent', 'New Order', 
                    sprintf('New order #%s received. %s', $orderRef, 
                        $order['service_type'] === 'room_service' ? 'Room service order.' : 'Customer order.'),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                $this->notifications->notifyRole('operation_manager', 'New Order', 
                    sprintf('New order #%s created via %s', $orderRef, $order['source'] ?? 'POS'),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;

            case 'confirmed':
                $this->notifications->notifyRole('kitchen', 'Order Confirmed', 
                    sprintf('Order #%s has been confirmed by %s', $orderRef, $userName),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                $this->notifications->notifyRole('service_agent', 'Order Confirmed', 
                    sprintf('Order #%s confirmed. Prepare for service.', $orderRef),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;

            case 'preparing':
                $this->notifications->notifyRole('service_agent', 'Order Preparing', 
                    sprintf('Order #%s is now being prepared in the kitchen.', $orderRef),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;

            case 'ready':
                $message = sprintf('Order #%s is ready for %s', $orderRef, 
                    $order['service_type'] === 'room_service' ? 'delivery to room ' . ($order['room_number'] ?? '') : 'pickup');
                $this->notifications->notifyRole('service_agent', 'Order Ready', $message,
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;

            case 'delivered':
                $this->notifications->notifyRole('finance_manager', 'Order Delivered', 
                    sprintf('Order #%s has been delivered to customer.', $orderRef),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;

            case 'completed':
                $this->notifications->notifyRole('finance_manager', 'Order Completed', 
                    sprintf('Order #%s completed successfully.', $orderRef),
                    ['order_id' => $order['id'], 'reference' => $orderRef]
                );
                break;
        }
    }

    /**
     * API endpoint for real-time order updates
     */
    public function poll(Request $request): void
    {
        Auth::requireRoles(['director', 'admin', 'operation_manager', 'cashier', 'service_agent', 'kitchen', 'finance_manager']);

        header('Content-Type: application/json');

        $lastCheck = (int)$request->input('last_check', time() - 60); // Default to 1 minute ago
        
        // Apply all filters from request
        $filters = [
            'status' => $request->input('status'),
            'order_type' => $request->input('order_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        // Get orders with filters
        $orders = $this->orders->all($filters, 100);
        $updated = [];

        foreach ($orders as $order) {
            $orderTime = strtotime($order['updated_at'] ?? $order['created_at']);
            if ($orderTime > $lastCheck) {
                $updated[] = [
                    'id' => $order['id'],
                    'reference' => $order['reference'],
                    'status' => $order['status'],
                    'updated_at' => $order['updated_at'] ?? $order['created_at'],
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'updated_orders' => $updated,
            'timestamp' => time(),
        ]);
    }

    /**
     * Handle order cancellation: restore inventory and process refunds
     */
    protected function handleOrderCancellation(array $order, int $userId, string $reason): void
    {
        $paymentStatus = $order['payment_status'] ?? 'pending';
        $wasPaid = in_array($paymentStatus, ['paid', 'completed']);
        $wasUnpaid = in_array($paymentStatus, ['pending', 'unpaid']);
        
        $db = db();
        
        // Update order payment status
        if ($wasUnpaid) {
            // If unpaid, set payment status to cancelled
            $stmt = $db->prepare('UPDATE orders SET payment_status = "cancelled" WHERE id = :id');
            $stmt->execute(['id' => (int)$order['id']]);
        }
        
        // Check if there's an associated POS sale
        $saleRepo = new \App\Repositories\PosSaleRepository();
        $sale = $saleRepo->findByReference($order['reference'] ?? '');
        
        if ($sale) {
            $salePaymentStatus = $sale['payment_status'] ?? 'pending';
            $saleWasUnpaid = in_array($salePaymentStatus, ['pending', 'unpaid']);
            $saleWasPaid = in_array($salePaymentStatus, ['paid', 'completed']);
            
            if ($saleWasUnpaid) {
                // If unpaid, set payment status to cancelled
                $stmt = $db->prepare('UPDATE pos_sales SET payment_status = "cancelled" WHERE id = :id');
                $stmt->execute(['id' => (int)$sale['id']]);
            } elseif ($saleWasPaid) {
                // If paid, set payment status to refunded
                $stmt = $db->prepare('UPDATE pos_sales SET payment_status = "refunded" WHERE id = :id');
                $stmt->execute(['id' => (int)$sale['id']]);
            }
        }
        
        // Only restore inventory if order was paid and inventory was deducted
        if ($wasPaid) {
            // Restore inventory if order items have inventory components
            $orderItems = $this->orders->getItems((int)$order['id']);
            $inventoryRepo = new \App\Repositories\InventoryRepository();
            $inventoryService = new \App\Services\Inventory\InventoryService();
            $locations = $inventoryRepo->locations();
            $locationId = (int)($locations[0]['id'] ?? 0);
            
            if ($locationId > 0) {
                foreach ($orderItems as $orderItem) {
                    $posItemId = (int)($orderItem['pos_item_id'] ?? 0);
                    if ($posItemId > 0) {
                        // Fetch components for this POS item
                        $stmt = $db->prepare('SELECT inventory_item_id, quantity_per_sale FROM pos_item_components WHERE pos_item_id = :id');
                        $stmt->execute(['id' => $posItemId]);
                        $components = $stmt->fetchAll() ?: [];
                        
                        foreach ($components as $component) {
                            $baseQty = (float)($component['quantity_per_sale'] ?? 0) * (float)($orderItem['quantity'] ?? 0);
                            if ($baseQty > 0) {
                                $inventoryItemId = (int)($component['inventory_item_id'] ?? 0);
                                if ($inventoryItemId > 0) {
                                    // Restore stock
                                    $inventoryRepo->addStock(
                                        $inventoryItemId,
                                        $locationId,
                                        $baseQty,
                                        'Order cancellation: ' . $order['reference'],
                                        'Inventory restored due to order cancellation: ' . $reason,
                                        'return'
                                    );
                                }
                            }
                        }
                    }
                }
            }
            
            // Log refund in payments table if it exists (only for paid orders)
            if ($sale && $saleWasPaid) {
                try {
                    $stmt = $db->prepare('
                        INSERT INTO payments (reference, amount, payment_method, status, notes, created_by)
                        VALUES (:ref, :amount, :method, "refunded", :notes, :user)
                    ');
                    $stmt->execute([
                        'ref' => $order['reference'],
                        'amount' => (float)($order['total'] ?? 0),
                        'method' => $sale['payment_type'] ?? 'unknown',
                        'notes' => 'Refund for cancelled order: ' . $reason,
                        'user' => $userId,
                    ]);
                } catch (\Exception $e) {
                    // Payments table might not exist or have different structure
                    error_log('Could not log refund payment: ' . $e->getMessage());
                }
            }
        }
    }
}
