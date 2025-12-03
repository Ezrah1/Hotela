<?php

namespace App\Modules\PaymentGateway\Controllers;

use App\Core\Controller;
use App\Core\Request;

class MpesaCallbackController extends Controller
{
    /**
     * Handle M-Pesa STK Push callback
     * This endpoint receives payment confirmations from Safaricom
     */
    public function handle(Request $request): void
    {
        // Get the raw JSON body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Log the callback for debugging
        error_log('M-Pesa Callback: ' . $json);

        // M-Pesa sends the callback as a JSON object
        if (isset($data['Body'])) {
            $stkCallback = $data['Body']['stkCallback'] ?? null;
            
            if ($stkCallback) {
                $merchantRequestId = $stkCallback['MerchantRequestID'] ?? '';
                $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? '';
                $resultCode = (int)($stkCallback['ResultCode'] ?? -1);
                $resultDesc = $stkCallback['ResultDesc'] ?? 'Unknown error';
                
                // Check if user cancelled (ResultCode 1032 means user cancelled)
                $isCancelled = ($resultCode === 1032) || (stripos($resultDesc, 'cancel') !== false);
                
                // If payment was successful, extract transaction details
                if ($resultCode === 0 && isset($stkCallback['CallbackMetadata'])) {
                    $metadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
                    $transactionData = [];
                    
                    foreach ($metadata as $item) {
                        $transactionData[$item['Name']] = $item['Value'] ?? null;
                    }
                    
                    $mpesaTransactionId = $transactionData['MpesaReceiptNumber'] ?? null;
                    $amount = $transactionData['Amount'] ?? null;
                    $phoneNumber = $transactionData['PhoneNumber'] ?? null;
                    
                    // Update payment transaction record
                    $this->updatePaymentTransaction($checkoutRequestId, 'completed', $mpesaTransactionId, $json);
                    
                    // Find and update POS sale if exists
                    $posSale = $this->findPosSaleByCheckoutRequest($checkoutRequestId);
                    if ($posSale) {
                        $this->updatePosSalePayment($posSale['id'], 'completed', $mpesaTransactionId);
                        
                        // Now deduct inventory and create folio entries
                        $this->processPosSaleCompletion($posSale['id']);
                    }
                    
                    // Find and update booking if exists
                    $booking = $this->findBookingByCheckoutRequest($checkoutRequestId);
                    if ($booking) {
                        $this->updateBookingPayment($booking['id'], 'completed', $mpesaTransactionId);
                    }
                    
                    // Find and process folio payment if exists
                    $folioPayment = $this->findFolioPaymentByCheckoutRequest($checkoutRequestId);
                    if ($folioPayment) {
                        error_log('Found folio payment transaction: ' . json_encode($folioPayment));
                        $this->processFolioPaymentCompletion($folioPayment, $amount, $mpesaTransactionId);
                    } else {
                        error_log('No folio payment found for checkout_request_id: ' . $checkoutRequestId);
                        // Try to find by merchant request ID as fallback
                        $folioPayment = $this->findFolioPaymentByMerchantRequest($merchantRequestId);
                        if ($folioPayment) {
                            error_log('Found folio payment by merchant_request_id: ' . json_encode($folioPayment));
                            $this->processFolioPaymentCompletion($folioPayment, $amount, $mpesaTransactionId);
                        }
                    }
                    
                    error_log('M-Pesa Payment Successful: Transaction ID: ' . $mpesaTransactionId . ', Amount: ' . $amount);
                } else {
                    // Payment failed or cancelled
                    $failureStatus = $isCancelled ? 'cancelled' : 'failed';
                    $this->updatePaymentTransaction($checkoutRequestId, $failureStatus, null, $json);
                    
                    $posSale = $this->findPosSaleByCheckoutRequest($checkoutRequestId);
                    if ($posSale) {
                        $this->updatePosSalePayment($posSale['id'], $failureStatus, null);
                    }
                    
                    $booking = $this->findBookingByCheckoutRequest($checkoutRequestId);
                    if ($booking) {
                        $this->updateBookingPayment($booking['id'], $failureStatus, null);
                    }
                    
                    // Update folio payment status if exists
                    $folioPayment = $this->findFolioPaymentByCheckoutRequest($checkoutRequestId);
                    if ($folioPayment) {
                        // Payment failed, no need to add entry
                        error_log('M-Pesa Folio Payment ' . ($isCancelled ? 'Cancelled' : 'Failed') . ' for reservation: ' . $folioPayment['reference_id']);
                    }
                    
                    error_log('M-Pesa Payment ' . ($isCancelled ? 'Cancelled' : 'Failed') . ': ' . $resultDesc . ' (ResultCode: ' . $resultCode . ')');
                }
            }
        }

        // Always return success to M-Pesa
        // M-Pesa expects a 200 OK response
        header('Content-Type: application/json');
        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully'
        ]);
    }

    protected function updatePaymentTransaction(string $checkoutRequestId, string $status, ?string $transactionId, string $callbackData): void
    {
        $stmt = db()->prepare('
            UPDATE payment_transactions 
            SET status = :status, mpesa_transaction_id = :transaction_id, callback_data = :callback_data, updated_at = NOW()
            WHERE checkout_request_id = :checkout_id
        ');
        $stmt->execute([
            'status' => $status,
            'transaction_id' => $transactionId,
            'callback_data' => $callbackData,
            'checkout_id' => $checkoutRequestId,
        ]);
    }

    protected function findPosSaleByCheckoutRequest(string $checkoutRequestId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM pos_sales WHERE mpesa_checkout_request_id = :checkout_id LIMIT 1');
        $stmt->execute(['checkout_id' => $checkoutRequestId]);
        return $stmt->fetch() ?: null;
    }

    protected function updatePosSalePayment(int $saleId, string $status, ?string $transactionId): void
    {
        $paymentStatus = $status === 'completed' ? 'paid' : ($status === 'failed' || $status === 'cancelled' ? 'failed' : 'pending');
        $stmt = db()->prepare('
            UPDATE pos_sales 
            SET mpesa_status = :status, payment_status = :payment_status, mpesa_transaction_id = :transaction_id
            WHERE id = :id
        ');
        $stmt->execute([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'transaction_id' => $transactionId,
            'id' => $saleId,
        ]);
    }

    protected function processPosSaleCompletion(int $saleId): void
    {
        $sale = db()->prepare('SELECT * FROM pos_sales WHERE id = :id LIMIT 1');
        $sale->execute(['id' => $saleId]);
        $saleData = $sale->fetch();
        
        if (!$saleData || $saleData['payment_status'] !== 'paid') {
            return; // Only process if payment is confirmed
        }
        
        // Get sale items
        $itemsStmt = db()->prepare('SELECT * FROM pos_sale_items WHERE sale_id = :sale_id');
        $itemsStmt->execute(['sale_id' => $saleId]);
        $items = $itemsStmt->fetchAll();
        
        // Deduct inventory
        $invRepo = new \App\Repositories\InventoryRepository();
        $invService = new \App\Services\Inventory\InventoryService();
        
        foreach ($items as $item) {
            $componentsStmt = db()->prepare('SELECT * FROM pos_item_components WHERE pos_item_id = :pos_item_id');
            $componentsStmt->execute(['pos_item_id' => $item['item_id']]);
            $components = $componentsStmt->fetchAll();
            
            foreach ($components as $component) {
                $qty = $component['quantity_per_sale'] * $item['quantity'];
                if ($qty > 0) {
                    $locationId = $invRepo->findBestLocationForItem((int)$component['inventory_item_id']);
                    if ($locationId) {
                        $invService->deductStock((int)$component['inventory_item_id'], $locationId, $qty, 'POS #' . $saleId, 'POS sale');
                    }
                }
            }
        }
        
        // Create folio entry if reservation exists
        if (!empty($saleData['reservation_id'])) {
            $folioRepo = new \App\Repositories\FolioRepository();
            $folio = $folioRepo->findByReservation((int)$saleData['reservation_id']);
            if (!$folio) {
                $folioRepo->create((int)$saleData['reservation_id']);
                $folio = $folioRepo->findByReservation((int)$saleData['reservation_id']);
            }
            if ($folio) {
                $folioRepo->addEntry((int)$folio['id'], 'POS sale #' . $saleId, (float)$saleData['total'], 'charge', 'pos');
            }
        }
    }

    protected function findBookingByCheckoutRequest(string $checkoutRequestId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM reservations WHERE mpesa_checkout_request_id = :checkout_id LIMIT 1');
        $stmt->execute(['checkout_id' => $checkoutRequestId]);
        return $stmt->fetch() ?: null;
    }

    protected function updateBookingPayment(int $bookingId, string $status, ?string $transactionId): void
    {
        $paymentStatus = $status === 'completed' ? 'paid' : ($status === 'failed' || $status === 'cancelled' ? 'unpaid' : 'unpaid');
        
        // If payment is completed, confirm the booking
        // If payment failed or cancelled, keep booking as pending
        $bookingStatus = ($status === 'completed') ? 'confirmed' : 'pending';
        
        $stmt = db()->prepare('
            UPDATE reservations 
            SET mpesa_status = :status, 
                payment_status = :payment_status, 
                mpesa_transaction_id = :transaction_id,
                status = :booking_status
            WHERE id = :id
        ');
        $stmt->execute([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'transaction_id' => $transactionId,
            'booking_status' => $bookingStatus,
            'id' => $bookingId,
        ]);
        
        // If payment completed, send confirmation email
        if ($status === 'completed') {
            try {
                $reservationRepo = new \App\Repositories\ReservationRepository();
                $reservation = $reservationRepo->findById($bookingId);
                
                if ($reservation && !empty($reservation['guest_email'])) {
                    $roomTypeRepo = new \App\Repositories\RoomTypeRepository();
                    $roomType = $roomTypeRepo->find((int)$reservation['room_type_id']);
                    
                    $emailService = new \App\Services\Email\EmailService();
                    $bookingData = [
                        'reference' => $reservation['reference'] ?? '',
                        'check_in' => $reservation['check_in'] ?? '',
                        'check_out' => $reservation['check_out'] ?? '',
                        'adults' => (int)($reservation['adults'] ?? 1),
                        'children' => (int)($reservation['children'] ?? 0),
                        'room_type_id' => (int)$reservation['room_type_id'],
                        'room_id' => $reservation['room_id'] ?? null,
                        'total_amount' => (float)($reservation['total_amount'] ?? 0),
                        'status' => 'confirmed',
                        'payment_status' => 'paid',
                        'payment_method' => $reservation['payment_method'] ?? 'mpesa',
                        'room_type_name' => $roomType['name'] ?? 'Room Type',
                    ];
                    
                    $guestData = [
                        'guest_name' => $reservation['guest_name'] ?? 'Guest',
                        'guest_email' => $reservation['guest_email'] ?? '',
                        'guest_phone' => $reservation['guest_phone'] ?? '',
                    ];
                    
                    $emailService->sendBookingConfirmation($bookingData, $guestData);
                }
            } catch (\Exception $e) {
                error_log('Failed to send confirmation email after M-Pesa payment: ' . $e->getMessage());
            }
        }
    }

    protected function findFolioPaymentByCheckoutRequest(string $checkoutRequestId): ?array
    {
        // Find by checkout_request_id and reference_code pattern (FOLIO-*)
        $stmt = db()->prepare('
            SELECT pt.*, r.id AS reservation_id, r.reference AS reservation_reference
            FROM payment_transactions pt
            LEFT JOIN reservations r ON r.id = pt.reference_id
            WHERE pt.checkout_request_id = :checkout_id 
            AND pt.reference_code LIKE :pattern
            LIMIT 1
        ');
        $stmt->execute([
            'checkout_id' => $checkoutRequestId,
            'pattern' => 'FOLIO-%'
        ]);
        $result = $stmt->fetch();
        
        if (!$result) {
            // Try without reservation join
            $stmt2 = db()->prepare('
                SELECT pt.*
                FROM payment_transactions pt
                WHERE pt.checkout_request_id = :checkout_id 
                AND pt.reference_code LIKE :pattern
                LIMIT 1
            ');
            $stmt2->execute([
                'checkout_id' => $checkoutRequestId,
                'pattern' => 'FOLIO-%'
            ]);
            $result = $stmt2->fetch();
        }
        
        return $result ?: null;
    }

    protected function findFolioPaymentByMerchantRequest(string $merchantRequestId): ?array
    {
        $stmt = db()->prepare('
            SELECT pt.*, r.id AS reservation_id, r.reference AS reservation_reference
            FROM payment_transactions pt
            LEFT JOIN reservations r ON r.id = pt.reference_id
            WHERE pt.merchant_request_id = :merchant_id 
            AND pt.reference_code LIKE :pattern
            LIMIT 1
        ');
        $stmt->execute([
            'merchant_id' => $merchantRequestId,
            'pattern' => 'FOLIO-%'
        ]);
        return $stmt->fetch() ?: null;
    }

    protected function processFolioPaymentCompletion(array $paymentTransaction, float $amount, string $mpesaTransactionId): void
    {
        $reservationId = (int)($paymentTransaction['reference_id'] ?? $paymentTransaction['reservation_id'] ?? 0);
        
        if (!$reservationId) {
            error_log('M-Pesa Folio Payment Error: No reservation ID found in payment transaction: ' . json_encode($paymentTransaction));
            return;
        }
        
        // Get or create folio
        $folioRepo = new \App\Repositories\FolioRepository();
        $folio = $folioRepo->findByReservation($reservationId);
        
        if (!$folio) {
            $folioRepo->create($reservationId);
            $folio = $folioRepo->findByReservation($reservationId);
        }
        
        if ($folio) {
            // Check if this payment entry already exists to avoid duplicates
            $entries = $folioRepo->entries((int)$folio['id']);
            $alreadyExists = false;
            foreach ($entries as $entry) {
                if ($entry['type'] === 'payment' && 
                    $entry['source'] === 'mpesa' && 
                    abs((float)$entry['amount'] - $amount) < 0.01 &&
                    strpos($entry['description'], $mpesaTransactionId) !== false) {
                    $alreadyExists = true;
                    break;
                }
            }
            
            if (!$alreadyExists) {
                // Add payment entry to folio
                $description = 'M-Pesa Payment - ' . ($paymentTransaction['reference_code'] ?? 'Folio Payment') . ' (TXN: ' . $mpesaTransactionId . ')';
                $folioRepo->addEntry((int)$folio['id'], $description, $amount, 'payment', 'mpesa');
                
                error_log('M-Pesa Folio Payment Completed: Reservation ' . $reservationId . ', Amount: ' . $amount . ', Transaction ID: ' . $mpesaTransactionId);
            } else {
                error_log('M-Pesa Folio Payment: Entry already exists, skipping duplicate. Reservation: ' . $reservationId . ', Amount: ' . $amount);
            }
        } else {
            error_log('M-Pesa Folio Payment Error: Could not create or find folio for reservation: ' . $reservationId);
        }
    }
}

