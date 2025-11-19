<?php

namespace App\Modules\PaymentGateway\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Payments\MpesaService;
use App\Support\Auth;

class MpesaTestController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireRoles(['admin']);
        
        $mpesaService = new MpesaService();
        $verification = $mpesaService->verifyConfiguration();
        
        $this->view('dashboard/payment-gateway/mpesa-test', [
            'verification' => $verification,
            'pageTitle' => 'M-Pesa Sandbox Test | Hotela',
        ]);
    }

    public function testStkPush(Request $request): void
    {
        Auth::requireRoles(['admin']);
        
        $phoneNumber = trim((string)$request->input('phone_number', ''));
        $amount = (float)$request->input('amount', 0);
        $accountReference = trim((string)$request->input('account_reference', 'TEST-' . time()));
        $transactionDesc = trim((string)$request->input('transaction_desc', 'M-Pesa Sandbox Test'));

        // Validate phone number
        if (empty($phoneNumber)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Phone number is required'
            ]);
            return;
        }

        // Validate amount
        if ($amount <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Amount must be greater than 0'
            ]);
            return;
        }

        try {
            $mpesaService = new MpesaService();
            $result = $mpesaService->stkPush($phoneNumber, $amount, $accountReference, $transactionDesc);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $result['customer_message'] ?? 'STK Push initiated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function queryStatus(Request $request): void
    {
        Auth::requireRoles(['admin']);
        
        $checkoutRequestId = $request->input('checkout_request_id');

        if (empty($checkoutRequestId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Checkout Request ID is required'
            ]);
            return;
        }

        try {
            $mpesaService = new MpesaService();
            $result = $mpesaService->queryStkStatus($checkoutRequestId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['result_desc'] ?? 'Status queried',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

