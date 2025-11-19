<?php

namespace App\Services\Payments;

use App\Services\Payments\MpesaService;
use RuntimeException;
use Exception;

class PaymentProcessingService
{
    protected MpesaService $mpesaService;

    public function __construct()
    {
        $this->mpesaService = new MpesaService();
    }

    /**
     * Process payment for any transaction type
     * 
     * @param string $method Payment method (cash, mpesa, card, bank_transfer, cheque, room, corporate)
     * @param float $amount Payment amount
     * @param array $options Additional options (phone, reference, description, etc.)
     * @return array Payment result with status and transaction details
     */
    public function processPayment(string $method, float $amount, array $options = []): array
    {
        $method = strtolower(trim($method));
        
        switch ($method) {
            case 'mpesa':
                return $this->processMpesaPayment($amount, $options);
            
            case 'cash':
                return $this->processCashPayment($amount, $options);
            
            case 'card':
                return $this->processCardPayment($amount, $options);
            
            case 'bank_transfer':
                return $this->processBankTransferPayment($amount, $options);
            
            case 'cheque':
                return $this->processChequePayment($amount, $options);
            
            case 'room':
            case 'room_charge':
                return $this->processRoomChargePayment($amount, $options);
            
            case 'corporate':
                return $this->processCorporatePayment($amount, $options);
            
            case 'pay_on_arrival':
                return $this->processPayOnArrivalPayment($amount, $options);
            
            default:
                throw new RuntimeException("Unknown payment method: {$method}");
        }
    }

    /**
     * Process M-Pesa payment
     */
    protected function processMpesaPayment(float $amount, array $options): array
    {
        $phone = trim($options['phone'] ?? '');
        $reference = $options['reference'] ?? $this->generateReference('MPESA');
        $description = $options['description'] ?? 'Payment';
        
        if (empty($phone)) {
            throw new RuntimeException('Phone number is required for M-Pesa payment');
        }
        
        try {
            $result = $this->mpesaService->stkPush(
                $phone,
                $amount,
                $reference,
                $description
            );
            
            return [
                'success' => true,
                'status' => 'pending',
                'payment_status' => 'pending',
                'method' => 'mpesa',
                'mpesa_phone' => $phone,
                'mpesa_checkout_request_id' => $result['checkout_request_id'] ?? null,
                'mpesa_merchant_request_id' => $result['merchant_request_id'] ?? null,
                'mpesa_status' => 'pending',
                'transaction_data' => $result
            ];
        } catch (Exception $e) {
            throw new RuntimeException('M-Pesa payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Process cash payment
     */
    protected function processCashPayment(float $amount, array $options): array
    {
        return [
            'success' => true,
            'status' => 'completed',
            'payment_status' => 'paid',
            'method' => 'cash',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process card payment
     */
    protected function processCardPayment(float $amount, array $options): array
    {
        // Card payments are typically processed immediately
        // In the future, this could integrate with a card payment gateway
        return [
            'success' => true,
            'status' => 'completed',
            'payment_status' => 'paid',
            'method' => 'card',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process bank transfer payment
     */
    protected function processBankTransferPayment(float $amount, array $options): array
    {
        // Bank transfers are typically confirmed manually
        return [
            'success' => true,
            'status' => 'pending',
            'payment_status' => 'pending',
            'method' => 'bank_transfer',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process cheque payment
     */
    protected function processChequePayment(float $amount, array $options): array
    {
        // Cheque payments require clearing time
        return [
            'success' => true,
            'status' => 'pending',
            'payment_status' => 'pending',
            'method' => 'cheque',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process room charge payment
     */
    protected function processRoomChargePayment(float $amount, array $options): array
    {
        $reservationId = $options['reservation_id'] ?? null;
        
        if (!$reservationId) {
            throw new RuntimeException('Reservation ID is required for room charge payment');
        }
        
        // Room charges are added to folio
        return [
            'success' => true,
            'status' => 'completed',
            'payment_status' => 'pending', // Will be paid when guest checks out
            'method' => 'room',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process corporate payment
     */
    protected function processCorporatePayment(float $amount, array $options): array
    {
        // Corporate payments are typically invoiced
        return [
            'success' => true,
            'status' => 'pending',
            'payment_status' => 'pending',
            'method' => 'corporate',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Process pay on arrival payment
     */
    protected function processPayOnArrivalPayment(float $amount, array $options): array
    {
        return [
            'success' => true,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'method' => 'pay_on_arrival',
            'mpesa_phone' => null,
            'mpesa_checkout_request_id' => null,
            'mpesa_merchant_request_id' => null,
            'mpesa_status' => null,
            'transaction_data' => []
        ];
    }

    /**
     * Generate a payment reference
     */
    protected function generateReference(string $prefix = 'PAY'): string
    {
        return $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /**
     * Get payment method display name
     */
    public static function getPaymentMethodName(string $method): string
    {
        $methods = [
            'cash' => 'Cash',
            'mpesa' => 'M-Pesa',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'room' => 'Room Charge',
            'room_charge' => 'Room Charge',
            'corporate' => 'Corporate',
            'pay_on_arrival' => 'Pay on Arrival',
        ];
        
        return $methods[strtolower($method)] ?? ucfirst($method);
    }

    /**
     * Check if payment method requires immediate confirmation
     */
    public static function requiresImmediateConfirmation(string $method): bool
    {
        $immediateMethods = ['cash', 'card'];
        return in_array(strtolower($method), $immediateMethods);
    }

    /**
     * Check if payment method is pending (requires callback or manual confirmation)
     */
    public static function isPendingPayment(string $method): bool
    {
        $pendingMethods = ['mpesa', 'bank_transfer', 'cheque', 'corporate', 'pay_on_arrival'];
        return in_array(strtolower($method), $pendingMethods);
    }
}

