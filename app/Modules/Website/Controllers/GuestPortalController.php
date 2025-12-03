<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ReservationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\GuestRequestRepository;
use App\Repositories\HousekeepingRepository;
use App\Repositories\GuestLoginCodeRepository;
use App\Repositories\GuestAccountRepository;
use App\Repositories\FolioRepository;
use App\Services\Notifications\NotificationService;
use App\Services\Email\EmailService;
use App\Support\GuestPortal;

class GuestPortalController extends Controller
{
    protected ReservationRepository $reservations;
    protected OrderRepository $orders;
    protected ReviewRepository $reviews;
    protected GuestRequestRepository $guestRequests;
    protected HousekeepingRepository $housekeeping;
    protected GuestLoginCodeRepository $loginCodes;
    protected GuestAccountRepository $guestAccounts;
    protected FolioRepository $folios;
    protected NotificationService $notifications;
    protected EmailService $emailService;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
        $this->orders = new OrderRepository();
        $this->reviews = new ReviewRepository();
        $this->guestRequests = new GuestRequestRepository();
        $this->housekeeping = new HousekeepingRepository();
        $this->loginCodes = new GuestLoginCodeRepository();
        $this->guestAccounts = new GuestAccountRepository();
        $this->folios = new FolioRepository();
        $this->notifications = new NotificationService();
        $this->emailService = new EmailService();
    }

    public function showLogin(Request $request): void
    {
        if (GuestPortal::check()) {
            header('Location: ' . base_url('guest/portal'));
            return;
        }

        $this->view('website/guest/login', [
            'redirect' => $request->input('redirect', base_url('guest/portal')),
            'pageTitle' => 'Guest Portal Login | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function authenticate(Request $request): void
    {
        $loginMethod = $request->input('login_method', 'password'); // 'password' or 'code'
        $redirect = $request->input('redirect', base_url('guest/portal'));

        if ($loginMethod === 'code') {
            // Login using email code
            $email = trim((string)$request->input('email'));
            $code = trim((string)$request->input('code'));

            if ($email === '' || $code === '') {
                header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=missing&method=code'));
                return;
            }

            // Verify the code
            $codeRecord = $this->loginCodes->findValidCode($email, $code);
            if (!$codeRecord) {
                header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=invalid_code&method=code'));
                return;
            }

            // Find reservations for this email
            $reservations = $this->reservations->listForGuest($email);
            if (empty($reservations)) {
                header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=no_bookings&method=code'));
                return;
            }

            // Use the most recent reservation
            $reservation = $reservations[0];
            
            // Mark code as used
            $this->loginCodes->markAsUsed($codeRecord['id']);

            // Update or create guest account
            $this->ensureGuestAccount($reservation);

            GuestPortal::login([
                'guest_name' => $reservation['guest_name'],
                'guest_email' => $reservation['guest_email'],
                'guest_phone' => $reservation['guest_phone'],
                'identifier' => strtolower($email),
                'identifier_type' => 'email',
                'reference' => $reservation['reference'],
            ]);

            header('Location: ' . $redirect);
            return;
        }

        // Password login
        $email = trim((string)$request->input('email'));
        $password = trim((string)$request->input('password'));

        if ($email === '' || $password === '') {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=missing&method=password'));
            return;
        }

        // Verify password
        if (!$this->guestAccounts->verifyPassword($email, $password)) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=invalid_credentials&method=password'));
            return;
        }

        // Find reservations for this email
        $reservations = $this->reservations->listForGuest($email);
        if (empty($reservations)) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=no_bookings&method=password'));
            return;
        }

        // Use the most recent reservation
        $reservation = $reservations[0];

        // Update last login
        $this->guestAccounts->updateLastLogin($email);

        GuestPortal::login([
            'guest_name' => $reservation['guest_name'],
            'guest_email' => $reservation['guest_email'],
            'guest_phone' => $reservation['guest_phone'],
            'identifier' => strtolower($email),
            'identifier_type' => 'email',
            'reference' => $reservation['reference'],
        ]);

        header('Location: ' . $redirect);
    }

    /**
     * Ensure guest account exists (create if needed)
     */
    protected function ensureGuestAccount(array $reservation): void
    {
        $email = strtolower(trim($reservation['guest_email'] ?? ''));
        if ($email === '') {
            return;
        }

        if (!$this->guestAccounts->exists($email)) {
            // Create account without password (they'll set it later)
            $this->guestAccounts->create([
                'guest_email' => $email,
                'password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), // Random password
                'guest_name' => $reservation['guest_name'] ?? null,
                'guest_phone' => $reservation['guest_phone'] ?? null,
            ]);
        } else {
            // Update info if needed
            $this->guestAccounts->updateInfo($email, [
                'guest_name' => $reservation['guest_name'] ?? null,
                'guest_phone' => $reservation['guest_phone'] ?? null,
            ]);
        }
    }

    /**
     * Request a login code via email
     */
    public function requestCode(Request $request): void
    {
        $email = trim((string)$request->input('email'));
        $redirect = $request->input('redirect', base_url('guest/portal'));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=invalid_email&method=code'));
            return;
        }

        // Check if there's a recent request (rate limiting)
        if ($this->loginCodes->hasRecentRequest($email, 2)) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=rate_limit&method=code'));
            return;
        }

        // Check if email has any bookings
        $reservations = $this->reservations->listForGuest($email);
        if (empty($reservations)) {
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&error=no_bookings&method=code'));
            return;
        }

        // Generate a 6-digit code
        $code = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save the code
        $this->loginCodes->create($email, $code, 15); // Expires in 15 minutes

        // Send email with code
        $brandName = settings('branding.name', 'Hotela');
        $subject = 'Your Guest Portal Login Code - ' . $brandName;
        
        $emailBody = $this->renderLoginCodeEmail($code, $brandName);
        
        $emailSent = $this->emailService->send($email, $subject, $emailBody, null, true);
        
        if (!$emailSent) {
            // Log the error for debugging
            error_log('Failed to send login code email to: ' . $email);
            
            // Still redirect with success to prevent code enumeration
            // But log the issue for admin review
            header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&success=code_sent&email=' . urlencode($email) . '&method=code'));
            return;
        }

        header('Location: ' . base_url('guest/login?redirect=' . urlencode($redirect) . '&success=code_sent&email=' . urlencode($email) . '&method=code'));
    }

    /**
     * Setup password for guest account
     */
    public function setupPassword(Request $request): void
    {
        $email = trim((string)$request->input('email'));
        $code = trim((string)$request->input('code'));
        $password = trim((string)$request->input('password'));
        $passwordConfirm = trim((string)$request->input('password_confirm'));

        if ($email === '' || $code === '' || $password === '' || $passwordConfirm === '') {
            header('Location: ' . base_url('guest/setup-password?error=missing&email=' . urlencode($email)));
            return;
        }

        if ($password !== $passwordConfirm) {
            header('Location: ' . base_url('guest/setup-password?error=password_mismatch&email=' . urlencode($email) . '&code=' . urlencode($code)));
            return;
        }

        if (strlen($password) < 8) {
            header('Location: ' . base_url('guest/setup-password?error=password_short&email=' . urlencode($email) . '&code=' . urlencode($code)));
            return;
        }

        // Verify the code
        $codeRecord = $this->loginCodes->findValidCode($email, $code);
        if (!$codeRecord) {
            header('Location: ' . base_url('guest/setup-password?error=invalid_code&email=' . urlencode($email)));
            return;
        }

        // Check if email has bookings
        $reservations = $this->reservations->listForGuest($email);
        if (empty($reservations)) {
            header('Location: ' . base_url('guest/setup-password?error=no_bookings&email=' . urlencode($email)));
            return;
        }

        $reservation = $reservations[0];

        // Create or update account with password
        if ($this->guestAccounts->exists($email)) {
            $this->guestAccounts->updatePassword($email, password_hash($password, PASSWORD_DEFAULT));
        } else {
            $this->guestAccounts->create([
                'guest_email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'guest_name' => $reservation['guest_name'] ?? null,
                'guest_phone' => $reservation['guest_phone'] ?? null,
            ]);
        }

        // Mark code as used
        $this->loginCodes->markAsUsed($codeRecord['id']);

        // Auto-login
        GuestPortal::login([
            'guest_name' => $reservation['guest_name'],
            'guest_email' => $reservation['guest_email'],
            'guest_phone' => $reservation['guest_phone'],
            'identifier' => strtolower($email),
            'identifier_type' => 'email',
            'reference' => $reservation['reference'],
        ]);

        header('Location: ' . base_url('guest/portal?success=password_set'));
    }

    /**
     * Show password setup page
     */
    public function showSetupPassword(Request $request): void
    {
        $email = trim((string)$request->input('email', ''));
        $code = trim((string)$request->input('code', ''));

        $this->view('website/guest/setup-password', [
            'email' => $email,
            'code' => $code,
            'error' => $request->input('error'),
            'pageTitle' => 'Setup Password | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(Request $request): void
    {
        $email = trim((string)$request->input('email'));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . base_url('guest/forgot-password?error=invalid_email'));
            return;
        }

        // Check if account exists
        if (!$this->guestAccounts->exists($email)) {
            // Don't reveal if account exists - always show success
            header('Location: ' . base_url('guest/forgot-password?success=reset_sent'));
            return;
        }

        // Check rate limiting
        if ($this->loginCodes->hasRecentRequest($email, 2)) {
            header('Location: ' . base_url('guest/forgot-password?error=rate_limit'));
            return;
        }

        // Generate reset code
        $code = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $this->loginCodes->create([
            'guest_email' => strtolower($email),
            'code' => $code,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
        ]);

        // Send email
        $brandName = settings('branding.name', 'Hotela');
        $emailBody = $this->renderLoginCodeEmail($code, $brandName) . "\n\nUse this code to reset your password.";
        $this->emailService->send(
            $email,
            'Password Reset Code - ' . $brandName,
            $emailBody
        );

        header('Location: ' . base_url('guest/reset-password?email=' . urlencode($email) . '&success=code_sent'));
    }

    /**
     * Show forgot password page
     */
    public function showForgotPassword(Request $request): void
    {
        $this->view('website/guest/forgot-password', [
            'error' => $request->input('error'),
            'success' => $request->input('success'),
            'pageTitle' => 'Forgot Password | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    /**
     * Show reset password page
     */
    public function showResetPassword(Request $request): void
    {
        $email = trim((string)$request->input('email', ''));

        $this->view('website/guest/reset-password', [
            'email' => $email,
            'error' => $request->input('error'),
            'success' => $request->input('success'),
            'pageTitle' => 'Reset Password | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): void
    {
        $email = trim((string)$request->input('email'));
        $code = trim((string)$request->input('code'));
        $password = trim((string)$request->input('password'));
        $passwordConfirm = trim((string)$request->input('password_confirm'));

        if ($email === '' || $code === '' || $password === '' || $passwordConfirm === '') {
            header('Location: ' . base_url('guest/reset-password?error=missing&email=' . urlencode($email)));
            return;
        }

        if ($password !== $passwordConfirm) {
            header('Location: ' . base_url('guest/reset-password?error=password_mismatch&email=' . urlencode($email) . '&code=' . urlencode($code)));
            return;
        }

        if (strlen($password) < 8) {
            header('Location: ' . base_url('guest/reset-password?error=password_short&email=' . urlencode($email) . '&code=' . urlencode($code)));
            return;
        }

        // Verify code
        $codeRecord = $this->loginCodes->findValidCode($email, $code);
        if (!$codeRecord) {
            header('Location: ' . base_url('guest/reset-password?error=invalid_code&email=' . urlencode($email)));
            return;
        }

        // Update password
        $this->guestAccounts->updatePassword($email, password_hash($password, PASSWORD_DEFAULT));

        // Mark code as used
        $this->loginCodes->markAsUsed($codeRecord['id']);

        header('Location: ' . base_url('guest/login?success=password_reset'));
    }

    /**
     * Render login code email template
     */
    protected function renderLoginCodeEmail(string $code, string $brandName): string
    {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Your Login Code</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #8b5cf6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
        .code-box { background: white; border: 2px dashed #8b5cf6; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .code { font-size: 32px; font-weight: bold; color: #8b5cf6; letter-spacing: 8px; font-family: monospace; }
        .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$brandName}</h1>
        </div>
        <div class='content'>
            <h2>Your Guest Portal Login Code</h2>
            <p>You requested a login code to access your guest portal. Use the code below to sign in:</p>
            
            <div class='code-box'>
                <div class='code'>{$code}</div>
            </div>
            
            <div class='warning'>
                <strong>Important:</strong> This code will expire in 15 minutes. Do not share this code with anyone.
            </div>
            
            <p>If you didn't request this code, please ignore this email.</p>
        </div>
        <div class='footer'>
            <p>This is an automated message from {$brandName}. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
        ";
    }

    public function logout(Request $request): void
    {
        GuestPortal::logout();
        $redirect = $request->input('redirect', base_url('/'));
        header('Location: ' . $redirect);
    }

    public function dashboard(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        // Get counts for dashboard
        $upcomingCount = $identifier ? count($this->reservations->upcomingForGuest($identifier)) : 0;
        $pastCount = $identifier ? count($this->reservations->pastForGuest($identifier)) : 0;
        $activeOrdersCount = $identifier ? count(array_filter(
            $this->orders->listForGuest($identifier),
            fn($order) => !in_array($order['status'], ['completed', 'cancelled'])
        )) : 0;

        $this->view('website/guest/dashboard', [
            'guest' => $session,
            'upcomingCount' => $upcomingCount,
            'pastCount' => $pastCount,
            'activeOrdersCount' => $activeOrdersCount,
            'pageTitle' => 'My Dashboard | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function upcomingBookings(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        $upcomingBookings = $identifier ? $this->reservations->upcomingForGuest($identifier) : [];

        $this->view('website/guest/upcoming-bookings', [
            'guest' => $session,
            'upcomingBookings' => $upcomingBookings,
            'pageTitle' => 'Upcoming Bookings | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function pastBookings(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        $pastBookings = $identifier ? $this->reservations->pastForGuest($identifier) : [];

        $this->view('website/guest/past-bookings', [
            'guest' => $session,
            'pastBookings' => $pastBookings,
            'pageTitle' => 'Past Bookings | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function activeOrders(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        $activeOrders = $identifier ? array_filter(
            $this->orders->listForGuest($identifier),
            fn($order) => !in_array($order['status'], ['completed', 'cancelled'])
        ) : [];

        $this->view('website/guest/active-orders', [
            'guest' => $session,
            'activeOrders' => array_values($activeOrders),
            'pageTitle' => 'Active Orders | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function booking(Request $request): void
    {
        $bookingId = (int)$request->input('id');
        $reference = trim((string)$request->input('ref', ''));
        $download = $request->input('download');

        if (!$bookingId && !$reference) {
            header('Location: ' . base_url('guest/portal?error=invalid_booking'));
            return;
        }

        $booking = $bookingId 
            ? $this->reservations->findById($bookingId)
            : $this->reservations->findByReference($reference);

        if (!$booking) {
            header('Location: ' . base_url('guest/portal?error=booking_not_found'));
            return;
        }

        // Receipt download is public - only requires booking reference
        if ($download === 'receipt') {
            $this->downloadReceipt($booking);
            return;
        }

        // Full booking details require login
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();

        // Verify guest access
        $identifier = $session['identifier'] ?? '';
        $hasAccess = false;
        if ($identifier) {
            if (str_contains($identifier, '@')) {
                $hasAccess = strtolower($booking['guest_email'] ?? '') === strtolower($identifier);
            } else {
                $sanitized = preg_replace('/[^0-9]/', '', $identifier);
                $bookingPhone = preg_replace('/[^0-9]/', '', $booking['guest_phone'] ?? '');
                $hasAccess = $sanitized === $bookingPhone;
            }
        }

        if (!$hasAccess) {
            header('Location: ' . base_url('guest/portal?error=access_denied'));
            return;
        }

        // Sync payment status from folio if folio exists
        $reservationId = (int)($booking['id'] ?? 0);
        if ($reservationId > 0) {
            $folio = $this->folios->findByReservation($reservationId);
            if ($folio) {
                // Recalculate folio to ensure balance is up-to-date and sync booking status
                $this->folios->recalculate((int)$folio['id']);
                
                // Reload booking to get updated payment status
                $booking = $this->reservations->findById($reservationId);
            }
        }

        $this->view('website/guest/booking', [
            'guest' => $session,
            'booking' => $booking,
            'pageTitle' => 'Booking Details | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    /**
     * Process payment for an existing booking
     */
    public function payBooking(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';
        $reference = trim($request->input('reference', ''));

        if (empty($reference)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Booking reference required']);
            return;
        }

        $booking = $this->reservations->validateGuestAccess($reference, $identifier);

        if (!$booking) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Booking not found']);
            return;
        }

        // Check if payment is already completed
        if (($booking['payment_status'] ?? 'unpaid') === 'paid') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Booking is already paid']);
            return;
        }

        $paymentMethod = trim($request->input('payment_method', 'mpesa'));
        $totalAmount = (float)($booking['total_amount'] ?? 0);

        if ($totalAmount <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid booking amount']);
            return;
        }

        try {
            $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
            
            $paymentOptions = [
                'reference' => $reference,
                'description' => 'Booking Payment - ' . $reference,
                'reservation_id' => (int)$booking['id'],
            ];
            
            if ($paymentMethod === 'mpesa') {
                $mpesaPhone = trim($request->input('phone', $booking['guest_phone'] ?? ''));
                if (empty($mpesaPhone)) {
                    http_response_code(422);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Phone number is required for M-Pesa payment']);
                    return;
                }
                $paymentOptions['phone'] = $mpesaPhone;
            }
            
            $paymentResult = $paymentProcessor->processPayment($paymentMethod, $totalAmount, $paymentOptions);
            
            // Update booking with payment information
            $reservationRepo = new \App\Repositories\ReservationRepository();
            $updateData = [
                'payment_method' => $paymentMethod,
                'mpesa_phone' => $paymentResult['mpesa_phone'] ?? null,
                'mpesa_checkout_request_id' => $paymentResult['mpesa_checkout_request_id'] ?? null,
                'mpesa_merchant_request_id' => $paymentResult['mpesa_merchant_request_id'] ?? null,
                'mpesa_status' => $paymentResult['mpesa_status'] ?? null,
            ];
            
            // If payment is immediately successful, update status
            if ($paymentResult['payment_status'] === 'paid') {
                $updateData['payment_status'] = 'paid';
                $updateData['status'] = 'confirmed';
            }
            
            $reservationRepo->updateStatus((int)$booking['id'], $updateData);
            
            // Create payment transaction record for M-Pesa
            if ($paymentMethod === 'mpesa' && !empty($paymentResult['mpesa_checkout_request_id'])) {
                $this->createPaymentTransaction(
                    'booking',
                    (int)$booking['id'],
                    $reference,
                    'mpesa',
                    $totalAmount,
                    $paymentResult['mpesa_phone'] ?? null,
                    $paymentResult['mpesa_checkout_request_id'],
                    $paymentResult['mpesa_merchant_request_id'] ?? null
                );
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $paymentMethod === 'mpesa' 
                    ? 'M-Pesa payment request sent. Please check your phone and approve the payment.'
                    : 'Payment processed successfully.',
                'payment_status' => $paymentResult['payment_status'],
                'redirect' => base_url('guest/booking?ref=' . urlencode($reference)),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Create payment transaction record
     */
    protected function createPaymentTransaction(string $type, int $referenceId, string $referenceCode, string $method, float $amount, ?string $phone, ?string $checkoutRequestId, ?string $merchantRequestId): void
    {
        $stmt = db()->prepare('
            INSERT INTO payment_transactions 
            (transaction_type, reference_id, reference_code, payment_method, amount, phone_number, checkout_request_id, merchant_request_id, status)
            VALUES 
            (:type, :reference_id, :reference_code, :method, :amount, :phone, :checkout_request_id, :merchant_request_id, "pending")
        ');
        $stmt->execute([
            'type' => $type,
            'reference_id' => $referenceId,
            'reference_code' => $referenceCode,
            'method' => $method,
            'amount' => $amount,
            'phone' => $phone,
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $merchantRequestId,
        ]);
    }

    /**
     * Generate and download booking receipt
     */
    protected function downloadReceipt(array $booking): void
    {
        $brandName = settings('branding.name', 'Hotela');
        $brandAddress = settings('branding.address', '');
        $brandPhone = settings('branding.contact_phone', '');
        $brandEmail = settings('branding.contact_email', '');
        
        $checkIn = new \DateTimeImmutable($booking['check_in'] ?? date('Y-m-d'));
        $checkOut = new \DateTimeImmutable($booking['check_out'] ?? date('Y-m-d'));
        $nights = max(1, $checkIn->diff($checkOut)->days);
        
        $roomTypeRepo = new \App\Repositories\RoomTypeRepository();
        $roomType = $roomTypeRepo->find((int)($booking['room_type_id'] ?? 0));
        
        $roomRepo = new \App\Repositories\RoomRepository();
        $room = $booking['room_id'] ? $roomRepo->find((int)$booking['room_id']) : null;
        
        // Set headers for download
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="booking-receipt-' . htmlspecialchars($booking['reference'] ?? 'receipt') . '.html"');
        
        // Generate receipt HTML
        $receiptHtml = $this->renderReceipt($booking, $roomType, $room, $checkIn, $checkOut, $nights, $brandName, $brandAddress, $brandPhone, $brandEmail);
        
        echo $receiptHtml;
    }

    /**
     * Render booking receipt HTML
     */
    protected function renderReceipt(array $booking, ?array $roomType, ?array $room, \DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut, int $nights, string $brandName, string $brandAddress, string $brandPhone, string $brandEmail): string
    {
        $reference = $booking['reference'] ?? 'N/A';
        $guestName = $booking['guest_name'] ?? 'Guest';
        $guestEmail = $booking['guest_email'] ?? '';
        $guestPhone = $booking['guest_phone'] ?? '';
        $totalAmount = (float)($booking['total_amount'] ?? 0);
        $paymentStatus = $booking['payment_status'] ?? 'unpaid';
        $paymentMethod = $booking['payment_method'] ?? 'pay_on_arrival';
        $roomTypeName = $roomType['name'] ?? 'Room Type';
        $roomNumber = $room ? ($room['room_number'] ?? $room['display_name'] ?? '') : '';
        
        $nightlyRate = $nights > 0 ? $totalAmount / $nights : $totalAmount;
        
        // Build QR code URL - link to online receipt (fully qualified with domain)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $receiptPath = base_url('guest/booking?ref=' . urlencode($reference) . '&download=receipt');
        $receiptUrl = $scheme . '://' . $host . $receiptPath;
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=1&data=' . urlencode($receiptUrl);
        
        $businessAddressHtml = $brandAddress ? '<p>' . htmlspecialchars($brandAddress) . '</p>' : '';
        $businessPhoneHtml = $brandPhone ? '<p>Tel: ' . htmlspecialchars($brandPhone) . '</p>' : '';
        $businessEmailHtml = $brandEmail ? '<p>Email: ' . htmlspecialchars($brandEmail) . '</p>' : '';
        $guestEmailHtml = $guestEmail ? '<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">' . htmlspecialchars($guestEmail) . '</span></div>' : '';
        $guestPhoneHtml = $guestPhone ? '<div class="detail-row"><span class="detail-label">Phone:</span><span class="detail-value">' . htmlspecialchars($guestPhone) . '</span></div>' : '';
        $roomNumberHtml = $roomNumber ? '<br><small>Room: ' . htmlspecialchars($roomNumber) . '</small>' : '';
        $paymentMethodText = $paymentMethod === 'mpesa' ? 'M-Pesa' : 'Pay on Arrival';
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($brandName) . ' - Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: white;
            padding: 2rem;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            page-break-inside: avoid;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .receipt-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0;
        }
        .business-info {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #475569;
            font-size: 0.85rem;
        }
        .business-info p {
            margin: 0.2rem 0;
        }
        .receipt-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .detail-section h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .detail-label {
            color: #64748b;
        }
        .detail-value {
            font-weight: 600;
            color: #1e293b;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        .items-table th {
            background: #f8fafc;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #1e293b;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }
        .total-row.grand-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 2px solid #e2e8f0;
        }
        .payment-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .payment-info h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
        }
        .footer p {
            margin: 0.5rem 0;
        }
        @media print {
            @page {
                size: A4;
                margin: 0.3in;
            }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { 
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11pt;
            }
            .receipt-container { 
                max-width: 100% !important;
                padding: 0.5rem !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                height: auto !important;
            }
            .receipt-header {
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                margin-bottom: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }
            .receipt-header h1 {
                font-size: 1.25rem !important;
                margin-bottom: 0 !important;
            }
            .business-info {
                margin-bottom: 0.75rem !important;
                font-size: 0.75rem !important;
            }
            .business-info p {
                margin: 0.15rem 0 !important;
            }
            .receipt-details {
                page-break-inside: avoid !important;
                margin-bottom: 0.75rem !important;
                gap: 1rem !important;
            }
            .detail-section h3 {
                font-size: 0.75rem !important;
                margin-bottom: 0.5rem !important;
                padding-bottom: 0.25rem !important;
            }
            .detail-row {
                margin-bottom: 0.35rem !important;
                font-size: 0.85rem !important;
            }
            .items-table {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                font-size: 0.8rem !important;
            }
            .items-table th,
            .items-table td {
                padding: 0.4rem !important;
            }
            .items-table thead {
                display: table-header-group;
            }
            .items-table tbody {
                display: table-row-group;
            }
            .items-table tr {
                page-break-inside: avoid !important;
            }
            .total-section {
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
            }
            .total-row {
                font-size: 0.9rem !important;
                margin-bottom: 0.35rem !important;
            }
            .total-row.grand-total {
                font-size: 1.1rem !important;
                margin-top: 0.5rem !important;
                padding-top: 0.5rem !important;
            }
            .payment-info {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                padding: 0.75rem !important;
            }
            .payment-info h3 {
                font-size: 0.75rem !important;
                margin-bottom: 0.5rem !important;
            }
            .footer {
                page-break-inside: avoid !important;
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
                font-size: 0.75rem !important;
            }
            .footer p {
                margin: 0.35rem 0 !important;
            }
            .footer img {
                width: 120px !important;
                height: 120px !important;
                max-width: 120px !important;
                max-height: 120px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .footer div[style*="inline-block"] {
                page-break-inside: avoid !important;
                margin: 0.75rem 0 !important;
                padding: 0.5rem !important;
            }
            .footer div[style*="margin: 1.5rem"] {
                margin: 0.75rem 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>' . htmlspecialchars($brandName) . '</h1>
        </div>
        
        <div class="business-info">
            ' . $businessAddressHtml . '
            ' . $businessPhoneHtml . '
            ' . $businessEmailHtml . '
        </div>
        
        <div class="receipt-details">
            <div class="detail-section">
                <h3>Booking Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Reference:</span>
                    <span class="detail-value">' . htmlspecialchars($reference) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">' . $checkIn->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value">' . $checkIn->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value">' . $checkOut->format('F j, Y') . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nights:</span>
                    <span class="detail-value">' . $nights . '</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Guest Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">' . htmlspecialchars($guestName) . '</span>
                </div>
                ' . $guestEmailHtml . '
                ' . $guestPhoneHtml . '
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Nights</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($roomTypeName) . '</strong>
                        ' . $roomNumberHtml . '
                    </td>
                    <td class="text-right">' . $nights . '</td>
                    <td class="text-right">KES ' . number_format($nightlyRate, 2) . '</td>
                    <td class="text-right"><strong>KES ' . number_format($totalAmount, 2) . '</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>KES ' . number_format($totalAmount, 2) . '</span>
            </div>
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>KES ' . number_format($totalAmount, 2) . '</span>
            </div>
        </div>
        
        <div class="payment-info">
            <h3>Payment Information</h3>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">' . htmlspecialchars($paymentMethodText) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value">' . ucfirst($paymentStatus) . '</span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your booking!</p>
            <p>This is an official receipt for your reservation.</p>
            <div style="margin: 1.5rem 0; text-align: center;">
                <p style="margin-bottom: 0.75rem; font-size: 0.9rem; color: #64748b; font-weight: 500;">Scan to view booking details</p>
                <div style="display: inline-block; padding: 1rem; background: white; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <img src="' . htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR Code - Booking Details" style="width: 200px; height: 200px; display: block; max-width: 100%;">
                </div>
            </div>
            <p style="margin-top: 1rem; font-size: 0.75rem; color: #64748b;">Generated on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
    </div>
    
    <script>
        // Auto-print when opened
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>';
        
        return $html;
    }

    public function orders(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        $allOrders = $identifier ? $this->orders->listForGuest($identifier) : [];
        
        // Separate current and past orders
        $currentOrders = array_filter($allOrders, fn($order) => !in_array($order['status'], ['completed', 'cancelled']));
        $pastOrders = array_filter($allOrders, fn($order) => in_array($order['status'], ['completed', 'cancelled']));

        $this->view('website/guest/orders', [
            'guest' => $session,
            'currentOrders' => array_values($currentOrders),
            'pastOrders' => array_values($pastOrders),
            'pageTitle' => 'My Orders | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function order(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $orderId = (int)$request->input('id');
        $reference = trim((string)$request->input('ref', ''));
        $download = $request->input('download');

        if (!$orderId && !$reference) {
            header('Location: ' . base_url('guest/orders?error=invalid_order'));
            return;
        }

        $order = $orderId 
            ? $this->orders->findById($orderId)
            : $this->orders->findByReference($reference);

        if (!$order) {
            header('Location: ' . base_url('guest/orders?error=order_not_found'));
            return;
        }

        // Verify guest access
        $identifier = $session['identifier'] ?? '';
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
            header('Location: ' . base_url('guest/orders?error=access_denied'));
            return;
        }

        // Handle receipt download
        if ($download === 'receipt' && in_array($order['payment_status'], ['paid', 'completed'])) {
            $this->downloadOrderReceipt($order);
            return;
        }

        $this->view('website/guest/order', [
            'guest' => $session,
            'order' => $order,
            'pageTitle' => 'Order Details | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function payOrder(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $reference = trim((string)$request->input('ref', ''));

        if (empty($reference)) {
            header('Location: ' . base_url('guest/orders?error=Order%20reference%20required'));
            return;
        }

        $order = $this->orders->findByReference($reference);
        if (!$order) {
            header('Location: ' . base_url('guest/orders?error=Order%20not%20found'));
            return;
        }

        // Verify guest access
        $identifier = $session['identifier'] ?? '';
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
            header('Location: ' . base_url('guest/orders?error=access_denied'));
            return;
        }

        // Check if payment is already paid
        if (in_array($order['payment_status'] ?? '', ['paid', 'completed'])) {
            header('Location: ' . base_url('guest/order?ref=' . urlencode($reference) . '&error=Order%20already%20paid'));
            return;
        }

        // Check if payment method can be changed (only cash/pay_on_delivery)
        $paymentMethod = $order['payment_type'] ?? '';
        if (!in_array($paymentMethod, ['cash', 'pay_on_delivery'])) {
            header('Location: ' . base_url('guest/order?ref=' . urlencode($reference) . '&error=Cannot%20change%20payment%20method'));
            return;
        }

        // Get enabled payment methods from website settings
        $websiteSettings = settings('website', []);
        $enabledPaymentMethods = $websiteSettings['enabled_payment_methods'] ?? ['cash'];
        
        // Backward compatibility
        if (!is_array($enabledPaymentMethods)) {
            if (!empty($websiteSettings['enable_mpesa_orders'])) {
                $enabledPaymentMethods = ['cash', 'mpesa'];
            } else {
                $enabledPaymentMethods = ['cash'];
            }
        }
        
        // Get configured payment gateways
        $paymentGateways = settings('payment_gateways', []);
        
        // Define available payment methods with their metadata
        $availablePaymentMethods = [
            'mpesa' => [
                'label' => 'M-Pesa',
                'description' => 'Pay via M-Pesa mobile money',
                'icon' => '💰',
                'requires_phone' => true,
            ],
            'bank' => [
                'label' => 'Bank Transfer',
                'description' => 'Pay via bank transfer',
                'icon' => '🏦',
                'requires_phone' => false,
            ],
            'cheque' => [
                'label' => 'Cheque',
                'description' => 'Pay via cheque',
                'icon' => '📝',
                'requires_phone' => false,
            ],
            'card' => [
                'label' => 'Card Payment',
                'description' => 'Credit and debit card processing',
                'icon' => '💳',
                'requires_phone' => false,
            ],
            'stripe' => [
                'label' => 'Stripe',
                'description' => 'Online payment processing via Stripe',
                'icon' => '💳',
                'requires_phone' => false,
            ],
            'paypal' => [
                'label' => 'PayPal',
                'description' => 'PayPal online payment system',
                'icon' => '💳',
                'requires_phone' => false,
            ],
        ];
        
        // Filter payment methods: only show digital methods (not cash) that are:
        // 1. In enabledPaymentMethods array, AND
        // 2. Configured and enabled in payment_gateways
        $availableDigitalMethods = [];
        foreach ($enabledPaymentMethods as $method) {
            // Skip cash (we're changing FROM cash TO digital)
            if ($method === 'cash') {
                continue;
            }
            
            // Normalize bank_transfer to bank
            if ($method === 'bank_transfer') {
                $method = 'bank';
            }
            
            // Check if method is configured and enabled in payment gateways
            if (isset($paymentGateways[$method]) && !empty($paymentGateways[$method]['enabled'])) {
                if (isset($availablePaymentMethods[$method])) {
                    $availableDigitalMethods[$method] = $availablePaymentMethods[$method];
                }
            }
        }

        $this->view('website/guest/pay-order', [
            'guest' => $session,
            'order' => $order,
            'availableMethods' => $availableDigitalMethods,
            'pageTitle' => 'Pay Order | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function changePaymentMethod(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $reference = trim((string)$request->input('ref', ''));
        $paymentMethod = trim((string)$request->input('payment_method', ''));
        $mpesaPhone = trim((string)$request->input('mpesa_phone', ''));

        if (empty($reference)) {
            header('Location: ' . base_url('guest/orders?error=Order%20reference%20required'));
            return;
        }

        $order = $this->orders->findByReference($reference);
        if (!$order) {
            header('Location: ' . base_url('guest/orders?error=Order%20not%20found'));
            return;
        }

        // Verify guest access
        $identifier = $session['identifier'] ?? '';
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
            header('Location: ' . base_url('guest/orders?error=access_denied'));
            return;
        }

        // Check if payment is already paid
        if (in_array($order['payment_status'] ?? '', ['paid', 'completed'])) {
            header('Location: ' . base_url('guest/order?ref=' . urlencode($reference) . '&error=Order%20already%20paid'));
            return;
        }

        // Get enabled payment methods from website settings
        $websiteSettings = settings('website', []);
        $enabledPaymentMethods = $websiteSettings['enabled_payment_methods'] ?? ['cash'];
        
        // Backward compatibility
        if (!is_array($enabledPaymentMethods)) {
            if (!empty($websiteSettings['enable_mpesa_orders'])) {
                $enabledPaymentMethods = ['cash', 'mpesa'];
            } else {
                $enabledPaymentMethods = ['cash'];
            }
        }
        
        // Get configured payment gateways
        $paymentGateways = settings('payment_gateways', []);
        
        // Normalize bank_transfer to bank (alias handling)
        if ($paymentMethod === 'bank_transfer') {
            $paymentMethod = 'bank';
        }
        
        // Validate payment method - must be enabled and configured
        $isValidMethod = false;
        if (in_array($paymentMethod, $enabledPaymentMethods) && $paymentMethod !== 'cash') {
            // Check if gateway is configured and enabled
            if (isset($paymentGateways[$paymentMethod]) && !empty($paymentGateways[$paymentMethod]['enabled'])) {
                $isValidMethod = true;
            }
        }
        
        if (!$isValidMethod) {
            header('Location: ' . base_url('guest/order/pay?ref=' . urlencode($reference) . '&error=Invalid%20or%20disabled%20payment%20method'));
            return;
        }

        // For M-Pesa, phone is required
        if ($paymentMethod === 'mpesa' && empty($mpesaPhone)) {
            header('Location: ' . base_url('guest/order/pay?ref=' . urlencode($reference) . '&error=Phone%20number%20required%20for%20M-Pesa'));
            return;
        }

        // Update order payment type
        $db = db();
        $stmt = $db->prepare('UPDATE orders SET payment_type = :payment_type WHERE id = :id');
        $stmt->execute([
            'payment_type' => $paymentMethod,
            'id' => (int)$order['id'],
        ]);

        // Update POS sale if it exists
        $saleRepo = new \App\Repositories\PosSaleRepository();
        $sale = $saleRepo->findByReference($reference);
        if ($sale) {
            $saleStmt = $db->prepare('UPDATE pos_sales SET payment_type = :payment_type, mpesa_phone = :mpesa_phone WHERE reference = :ref');
            $saleStmt->execute([
                'payment_type' => $paymentMethod,
                'mpesa_phone' => $paymentMethod === 'mpesa' ? $mpesaPhone : null,
                'ref' => $reference,
            ]);
        }

        // Normalize bank_transfer to bank
        if ($paymentMethod === 'bank_transfer') {
            $paymentMethod = 'bank';
        }
        
        // If M-Pesa, process payment
        if ($paymentMethod === 'mpesa') {
            try {
                $paymentProcessor = new \App\Services\Payments\PaymentProcessingService();
                $paymentResult = $paymentProcessor->processPayment('mpesa', (float)$order['total'], [
                    'reference' => $reference,
                    'description' => 'Order Payment - ' . $reference,
                    'phone' => $mpesaPhone,
                ]);

                $mpesaCheckoutRequestId = $paymentResult['mpesa_checkout_request_id'] ?? null;
                $mpesaMerchantRequestId = $paymentResult['mpesa_merchant_request_id'] ?? null;

                if ($mpesaCheckoutRequestId && $sale) {
                    $mpesaStmt = $db->prepare('UPDATE pos_sales SET mpesa_checkout_request_id = :checkout_id, mpesa_merchant_request_id = :merchant_id, mpesa_status = :status WHERE reference = :ref');
                    $mpesaStmt->execute([
                        'checkout_id' => $mpesaCheckoutRequestId,
                        'merchant_id' => $mpesaMerchantRequestId,
                        'status' => 'pending',
                        'ref' => $reference,
                    ]);
                }

                // Redirect to payment waiting page
                header('Location: ' . base_url('order/payment-waiting?ref=' . urlencode($reference)));
                return;
            } catch (\Exception $e) {
                error_log('Payment processing error: ' . $e->getMessage());
                header('Location: ' . base_url('guest/order/pay?ref=' . urlencode($reference) . '&error=Payment%20processing%20failed'));
                return;
            }
        }

        // For other payment methods, just update and redirect back
        header('Location: ' . base_url('guest/order?ref=' . urlencode($reference) . '&success=Payment%20method%20updated'));
    }

    public function profile(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        // Get all bookings to extract preferences
        $allBookings = $identifier ? $this->reservations->listForGuest($identifier) : [];
        
        // Get all folios for this guest
        $guestEmail = $session['guest_email'] ?? null;
        $guestPhone = $session['guest_phone'] ?? null;
        $allFolios = [];
        
        if ($guestEmail || $guestPhone) {
            $allFolios = $this->folios->findAllByGuest($guestEmail, $guestPhone);
        }

        $this->view('website/guest/profile', [
            'guest' => $session,
            'bookings' => $allBookings,
            'folios' => $allFolios,
            'pageTitle' => 'My Profile | ' . settings('branding.name', 'Hotela'),
        ]);
    }
    
    public function folios(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';
        
        $guestEmail = $session['guest_email'] ?? null;
        $guestPhone = $session['guest_phone'] ?? null;
        $allFolios = [];
        
        if ($guestEmail || $guestPhone) {
            $allFolios = $this->folios->findAllByGuest($guestEmail, $guestPhone);
        }
        
        // Get folio entries for each folio
        $foliosWithEntries = [];
        foreach ($allFolios as $folio) {
            $entries = $this->folios->entries((int)$folio['id']);
            $folio['entries'] = $entries;
            $foliosWithEntries[] = $folio;
        }

        $this->view('website/guest/folios', [
            'guest' => $session,
            'folios' => $foliosWithEntries,
            'pageTitle' => 'My Folios | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function notifications(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        // Get notifications for guest (from reservations and orders)
        $notifications = [];
        
        // Booking notifications
        $upcomingBookings = $identifier ? $this->reservations->upcomingForGuest($identifier) : [];
        foreach ($upcomingBookings as $booking) {
            if ($booking['status'] === 'confirmed') {
                $notifications[] = [
                    'type' => 'booking',
                    'message' => 'Your booking has been confirmed.',
                    'date' => $booking['created_at'],
                    'link' => base_url('guest/booking?ref=' . urlencode($booking['reference'])),
                ];
            }
            if ($booking['check_in_status'] === 'checked_in') {
                $notifications[] = [
                    'type' => 'booking',
                    'message' => 'Your room is ready for check-in.',
                    'date' => $booking['check_in'],
                    'link' => base_url('guest/booking?ref=' . urlencode($booking['reference'])),
                ];
            }
        }

        // Order notifications
        $activeOrders = $identifier ? array_filter(
            $this->orders->listForGuest($identifier),
            fn($order) => !in_array($order['status'], ['completed', 'cancelled'])
        ) : [];
        
        foreach ($activeOrders as $order) {
            if ($order['status'] === 'ready') {
                $notifications[] = [
                    'type' => 'order',
                    'message' => sprintf('Your order #%s is ready.', $order['reference']),
                    'date' => $order['updated_at'],
                    'link' => base_url('guest/order?ref=' . urlencode($order['reference'])),
                ];
            }
        }

        // Sort by date (newest first)
        usort($notifications, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        $this->view('website/guest/notifications', [
            'guest' => $session,
            'notifications' => $notifications,
            'pageTitle' => 'Notifications | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function reviews(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();
        $identifier = $session['identifier'] ?? '';

        // Get guest's reviews
        $myReviews = $identifier ? $this->reviews->listForGuest($identifier) : [];

        // Get all approved reviews for display
        $allReviews = $this->reviews->getApproved([], 50);
        $averageRating = $this->reviews->getAverageRating();
        $totalReviews = $this->reviews->getRatingCount();

        $this->view('website/guest/reviews', [
            'guest' => $session,
            'myReviews' => $myReviews,
            'allReviews' => $allReviews,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
            'pageTitle' => 'Reviews | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function createReview(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();

        if ($request->method() !== 'POST') {
            header('Location: ' . base_url('guest/reviews?error=invalid_method'));
            return;
        }

        $reservationId = $request->input('reservation_id');
        $reservationId = ($reservationId !== null && $reservationId !== '' && $reservationId !== 'null') ? (int)$reservationId : null;
        $rating = (int)$request->input('rating');
        $title = trim((string)$request->input('title', ''));
        $comment = trim((string)$request->input('comment', ''));
        $category = $request->input('category', 'overall');

        if ($rating < 1 || $rating > 5) {
            header('Location: ' . base_url('guest/portal?error=invalid_rating'));
            return;
        }

        // Verify reservation belongs to guest if provided
        if ($reservationId) {
            $booking = $this->reservations->findById($reservationId);
            if (!$booking) {
                header('Location: ' . base_url('guest/portal?error=booking_not_found'));
                return;
            }

            $identifier = $session['identifier'] ?? '';
            $hasAccess = false;
            if ($identifier) {
                if (str_contains($identifier, '@')) {
                    $hasAccess = strtolower($booking['guest_email'] ?? '') === strtolower($identifier);
                } else {
                    $sanitized = preg_replace('/[^0-9]/', '', $identifier);
                    $bookingPhone = preg_replace('/[^0-9]/', '', $booking['guest_phone'] ?? '');
                    $hasAccess = $sanitized === $bookingPhone;
                }
            }

            if (!$hasAccess) {
                header('Location: ' . base_url('guest/portal?error=access_denied'));
                return;
            }
        }

        $this->reviews->create([
            'reservation_id' => $reservationId,
            'guest_name' => $session['guest_name'] ?? 'Guest',
            'guest_email' => $session['guest_email'] ?? '',
            'guest_phone' => $session['guest_phone'] ?? null,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'category' => $category,
            'status' => 'pending', // Will be approved by admin
        ]);

        header('Location: ' . base_url('guest/portal?success=review_submitted'));
    }

    public function contact(): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();

        $this->view('website/guest/contact', [
            'guest' => $session,
            'pageTitle' => 'Contact Us | ' . settings('branding.name', 'Hotela'),
        ]);
    }

    public function submitContact(Request $request): void
    {
        GuestPortal::requireLogin('guest/portal');
        $session = GuestPortal::user();

        if ($request->method() !== 'POST') {
            header('Location: ' . base_url('guest/contact?error=invalid_method'));
            return;
        }

        $subject = trim((string)$request->input('subject', ''));
        $message = trim((string)$request->input('message', ''));
        $bookingRef = trim((string)$request->input('booking_reference', ''));

        if (empty($subject) || empty($message)) {
            header('Location: ' . base_url('guest/contact?error=missing_fields'));
            return;
        }

        // Here you would typically send an email or save to a contact_messages table
        // For now, we'll just redirect with success
        // TODO: Implement email sending or database storage

        header('Location: ' . base_url('guest/contact?success=message_sent'));
    }

    /**
     * Generate and download order receipt
     */
    protected function downloadOrderReceipt(array $order): void
    {
        $branding = settings('branding', []);
        $brandName = $branding['name'] ?? 'Hotela';
        $brandAddress = $branding['address'] ?? '';
        $brandPhone = $branding['phone'] ?? '';
        $brandEmail = $branding['email'] ?? '';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="order-receipt-' . htmlspecialchars($order['reference'] ?? 'receipt') . '.html"');

        $receiptHtml = $this->renderOrderReceipt($order, $brandName, $brandAddress, $brandPhone, $brandEmail);
        echo $receiptHtml;
    }

    /**
     * Render order receipt HTML
     */
    protected function renderOrderReceipt(array $order, string $brandName, string $brandAddress, string $brandPhone, string $brandEmail): string
    {
        $reference = $order['reference'] ?? 'N/A';
        $orderDate = date('F j, Y g:i A', strtotime($order['created_at'] ?? 'now'));
        $customerName = $order['customer_name'] ?? 'Guest';
        $customerPhone = $order['customer_phone'] ?? '';
        $customerEmail = $order['customer_email'] ?? '';
        $serviceType = ucfirst(str_replace('_', ' ', $order['service_type'] ?? 'pickup'));
        $paymentType = ucfirst($order['payment_type'] ?? 'cash');
        $total = (float)($order['total'] ?? 0);
        $items = $order['items'] ?? [];

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($brandName) . ' - Order Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1f2937;
            background: #f9fafb;
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .receipt-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .receipt-header p {
            color: #6b7280;
            font-size: 13px;
        }
        .receipt-details {
            margin-bottom: 25px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #1f2937;
            text-align: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-label {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }
        .total-value {
            font-size: 24px;
            font-weight: 700;
            color: #059669;
        }
        .receipt-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }
        @media screen {
            .print-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #059669;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }
            .print-btn:hover {
                background: #047857;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>' . htmlspecialchars($brandName) . '</h1>
            <p>Order Receipt</p>
        </div>

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Order Reference:</span>
                <span class="detail-value">' . htmlspecialchars($reference) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">' . htmlspecialchars($orderDate) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer:</span>
                <span class="detail-value">' . htmlspecialchars($customerName) . '</span>
            </div>
            ' . (!empty($customerPhone) ? '<div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">' . htmlspecialchars($customerPhone) . '</span>
            </div>' : '') . '
            ' . (!empty($customerEmail) ? '<div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">' . htmlspecialchars($customerEmail) . '</span>
            </div>' : '') . '
            <div class="detail-row">
                <span class="detail-label">Service Type:</span>
                <span class="detail-value">' . htmlspecialchars($serviceType) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">' . htmlspecialchars($paymentType) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value" style="color: #059669; font-weight: 600;">Paid</span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                ' . implode('', array_map(function($item) {
                    return '<tr>
                        <td>' . htmlspecialchars($item['item_name'] ?? 'Item') . '</td>
                        <td class="text-right">' . number_format((float)($item['quantity'] ?? 1), 0) . '</td>
                        <td class="text-right">KES ' . number_format((float)($item['unit_price'] ?? 0), 2) . '</td>
                        <td class="text-right">KES ' . number_format((float)($item['line_total'] ?? 0), 2) . '</td>
                    </tr>';
                }, $items)) . '
            </tbody>
        </table>

        <div class="total-row">
            <span class="total-label">Total Amount:</span>
            <span class="total-value">KES ' . number_format($total, 2) . '</span>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your order!</p>
            ' . (!empty($brandAddress) ? '<p>' . htmlspecialchars($brandAddress) . '</p>' : '') . '
            ' . (!empty($brandPhone) ? '<p>Phone: ' . htmlspecialchars($brandPhone) . '</p>' : '') . '
            ' . (!empty($brandEmail) ? '<p>Email: ' . htmlspecialchars($brandEmail) . '</p>' : '') . '
            <p style="margin-top: 15px; font-size: 11px; color: #9ca3af;">This is an official receipt for your order.</p>
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()">🖨️ Print Receipt</button>

    <script>
        // Auto-print when opened (only if opened in new window)
        if (window.opener) {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>';
    }
}

