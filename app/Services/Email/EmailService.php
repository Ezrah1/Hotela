<?php

namespace App\Services\Email;

use App\Services\Settings\SettingStore;

// Check if PHPMailer is available
if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    // Try to load PHPMailer from vendor directory (check multiple possible paths)
    $possiblePaths = [
        BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php', // Composer install
        BASE_PATH . '/vendor/phpmailer/src/PHPMailer.php', // Manual install
    ];
    
    foreach ($possiblePaths as $phpmailerPath) {
        if (file_exists($phpmailerPath)) {
            require_once $phpmailerPath;
            $basePath = dirname($phpmailerPath);
            require_once $basePath . '/SMTP.php';
            require_once $basePath . '/Exception.php';
            break;
        }
    }
}

class EmailService
{
    protected SettingStore $settings;
    protected $mailer = null;

    public function __construct(?SettingStore $settings = null)
    {
        $this->settings = $settings ?? new SettingStore();
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $body, ?string $toName = null, bool $isHTML = true): bool
    {
        $notifications = $this->settings->group('notifications');
        
        // Check if email is enabled
        if (empty($notifications['email_enabled'])) {
            error_log('Email sending is disabled in settings');
            return false;
        }

        try {
            $mailer = $this->getMailer();
            
            // Get branding settings for fallback
            $branding = $this->settings->group('branding');
            $integrations = $this->settings->group('integrations');
            
            // Determine desired From email and name
            $desiredFromEmail = $notifications['default_from_email'] 
                ?? $branding['contact_email'] 
                ?? 'noreply@hotela.local';
            $fromName = $notifications['default_from_name'] 
                ?? $branding['name'] 
                ?? 'Hotela';
            
            // Check if using Gmail SMTP
            $smtpHost = $integrations['smtp_host'] ?? env('SMTP_HOST', 'localhost');
            $smtpUser = $integrations['smtp_username'] ?? env('SMTP_USERNAME', '');
            $isGmail = strpos(strtolower($smtpHost), 'gmail') !== false || strpos(strtolower($smtpUser), 'gmail') !== false;
            
            // Gmail requires From address to match authenticated email
            // If using Gmail and desired email doesn't match SMTP username, use SMTP username as From
            if ($isGmail && !empty($smtpUser) && strtolower($desiredFromEmail) !== strtolower($smtpUser)) {
                // Use SMTP username as From address
                $mailer->setFrom($smtpUser, $fromName);
                // Set Reply-To to desired email so replies go to the right place
                $mailer->addReplyTo($desiredFromEmail, $fromName);
            } else {
                // Use desired From address
                $mailer->setFrom($desiredFromEmail, $fromName);
                // Set Reply-To to contact email if different
                $replyToEmail = $branding['contact_email'] ?? $desiredFromEmail;
                if ($replyToEmail !== $desiredFromEmail) {
                    $mailer->addReplyTo($replyToEmail, $fromName);
                }
            }
            
            $mailer->addAddress($to, $toName ?? '');
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->isHTML($isHTML);
            
            if (!$mailer->send()) {
                error_log('Email sending failed: ' . $mailer->ErrorInfo);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Email service error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email with template
     */
    public function sendTemplate(string $to, string $template, array $data = [], ?string $toName = null): bool
    {
        $body = $this->renderTemplate($template, $data);
        $subject = $data['subject'] ?? 'Notification from Hotela';
        
        return $this->send($to, $subject, $body, $toName);
    }

    /**
     * Get configured PHPMailer instance
     */
    protected function getMailer()
    {
        if ($this->mailer !== null) {
            return $this->mailer;
        }

        // Check if PHPMailer is available
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            throw new \RuntimeException('PHPMailer is not installed. Please install it via Composer: composer require phpmailer/phpmailer');
        }

        $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        $integrations = $this->settings->group('integrations');
        $notifications = $this->settings->group('notifications');
        
        // Get SMTP configuration from settings or environment
        $smtpHost = $integrations['smtp_host'] ?? env('SMTP_HOST', 'localhost');
        $smtpPort = (int)($integrations['smtp_port'] ?? env('SMTP_PORT', 587));
        $smtpUser = $integrations['smtp_username'] ?? env('SMTP_USERNAME', '');
        $smtpPass = $integrations['smtp_password'] ?? env('SMTP_PASSWORD', '');
        $smtpEncryption = $integrations['smtp_encryption'] ?? env('SMTP_ENCRYPTION', 'tls');
        $smtpAuth = !empty($integrations['smtp_auth']) || !empty($smtpUser);
        
        // Configure SMTP if host is not localhost
        if ($smtpHost !== 'localhost' && $smtpHost !== '127.0.0.1') {
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtpHost;
            $this->mailer->Port = $smtpPort;
            $this->mailer->SMTPAuth = $smtpAuth;
            
            if ($smtpAuth) {
                $this->mailer->Username = $smtpUser;
                $this->mailer->Password = $smtpPass;
            }
            
            if ($smtpEncryption === 'ssl') {
                $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpEncryption === 'tls') {
                $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Debug mode (only in development)
            $this->mailer->SMTPDebug = env('APP_DEBUG', false) ? 2 : 0;
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("SMTP Debug ($level): $str");
            };
        } else {
            // Use PHP mail() function for localhost
            $this->mailer->isMail();
        }
        
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64';
        
        return $this->mailer;
    }

    /**
     * Render email template
     */
    protected function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = BASE_PATH . '/resources/views/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            // Fallback to simple template
            return $this->renderSimpleTemplate($data);
        }
        
        ob_start();
        extract($data);
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Render simple fallback template
     */
    protected function renderSimpleTemplate(array $data = []): string
    {
        $body = $data['body'] ?? $data['message'] ?? '';
        $title = $data['title'] ?? 'Notification';
        
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #8b5cf6; color: white; padding: 20px; text-align: center; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$title}</h1>
        </div>
        <div class='content'>
            " . nl2br(htmlspecialchars($body)) . "
        </div>
        <div class='footer'>
            <p>This is an automated message from Hotela. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
        ";
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(array $booking, array $guest): bool
    {
        $data = [
            'title' => 'Booking Confirmation',
            'subject' => 'Booking Confirmation - ' . ($booking['reference'] ?? ''),
            'booking' => $booking,
            'guest' => $guest,
            'body' => $this->formatBookingConfirmation($booking, $guest),
        ];
        
        return $this->sendTemplate($guest['email'] ?? $guest['guest_email'] ?? '', 'booking-confirmation', $data, $guest['name'] ?? $guest['guest_name'] ?? null);
    }

    /**
     * Format booking confirmation message
     */
    protected function formatBookingConfirmation(array $booking, array $guest): string
    {
        $reference = $booking['reference'] ?? 'N/A';
        $checkIn = $booking['check_in'] ?? 'N/A';
        $checkOut = $booking['check_out'] ?? 'N/A';
        $guestName = $guest['name'] ?? $guest['guest_name'] ?? 'Guest';
        
        return "Dear {$guestName},\n\n" .
               "Thank you for your booking! Your reservation has been confirmed.\n\n" .
               "Booking Reference: {$reference}\n" .
               "Check-in: {$checkIn}\n" .
               "Check-out: {$checkOut}\n\n" .
               "We look forward to welcoming you!\n\n" .
               "Best regards,\n" .
               "Hotela Team";
    }

    /**
     * Send payment completion email for pending bookings
     */
    public function sendPaymentCompletionEmail(array $booking, array $guest): bool
    {
        $data = [
            'title' => 'Complete Your Booking Payment',
            'subject' => 'Action Required: Complete Payment for Booking ' . ($booking['reference'] ?? ''),
            'booking' => $booking,
            'guest' => $guest,
            'body' => $this->formatPaymentCompletionMessage($booking, $guest),
        ];
        
        return $this->sendTemplate($guest['email'] ?? $guest['guest_email'] ?? '', 'payment-completion', $data, $guest['name'] ?? $guest['guest_name'] ?? null);
    }

    /**
     * Format payment completion message
     */
    protected function formatPaymentCompletionMessage(array $booking, array $guest): string
    {
        $reference = $booking['reference'] ?? 'N/A';
        $checkIn = $booking['check_in'] ?? 'N/A';
        $checkOut = $booking['check_out'] ?? 'N/A';
        $totalAmount = number_format((float)($booking['total_amount'] ?? 0), 2);
        $paymentMethod = $booking['payment_method'] ?? 'M-Pesa';
        $guestName = $guest['name'] ?? $guest['guest_name'] ?? 'Guest';
        $brandName = settings('branding.name', 'Hotela');
        $portalUrl = base_url('guest/portal');
        
        $message = "Dear {$guestName},\n\n";
        $message .= "Thank you for initiating your booking! We have reserved your room, but payment is required to confirm your reservation.\n\n";
        $message .= "Booking Reference: {$reference}\n";
        $message .= "Check-in: {$checkIn}\n";
        $message .= "Check-out: {$checkOut}\n";
        $message .= "Total Amount: KES {$totalAmount}\n\n";
        
        if ($paymentMethod === 'mpesa') {
            $mpesaPhone = $booking['mpesa_phone'] ?? '';
            $message .= "M-Pesa Payment:\n";
            $message .= "Please complete the M-Pesa payment on your phone. If you haven't received the prompt, please check your phone and approve the payment.\n";
            if ($mpesaPhone) {
                $message .= "Payment Phone: {$mpesaPhone}\n";
            }
            $message .= "\nOnce payment is confirmed, you will receive a booking confirmation email.\n\n";
        } else {
            $message .= "Payment Method: " . ucfirst(str_replace('_', ' ', $paymentMethod)) . "\n";
            $message .= "Please complete your payment to confirm your booking.\n\n";
        }
        
        $message .= "You can view your booking and payment status here: {$portalUrl}\n\n";
        $message .= "If you need assistance, please contact us immediately.\n\n";
        $message .= "Best regards,\n";
        $message .= "{$brandName} Team";
        
        return $message;
    }

    /**
     * Send checkout follow-up email
     */
    public function sendCheckoutFollowUp(array $booking, array $guest): bool
    {
        $data = [
            'title' => 'Thank You for Your Stay',
            'subject' => 'Thank You for Staying with Us - ' . ($booking['reference'] ?? ''),
            'booking' => $booking,
            'guest' => $guest,
            'body' => $this->formatCheckoutFollowUp($booking, $guest),
        ];
        
        return $this->sendTemplate($guest['email'] ?? $guest['guest_email'] ?? '', 'checkout-followup', $data, $guest['name'] ?? $guest['guest_name'] ?? null);
    }

    /**
     * Format checkout follow-up message
     */
    protected function formatCheckoutFollowUp(array $booking, array $guest): string
    {
        $reference = $booking['reference'] ?? 'N/A';
        $checkIn = $booking['check_in'] ?? 'N/A';
        $checkOut = $booking['check_out'] ?? 'N/A';
        $guestName = $guest['name'] ?? $guest['guest_name'] ?? 'Guest';
        $brandName = settings('branding.name', 'Hotela');
        $brandEmail = settings('branding.contact_email', '');
        $brandPhone = settings('branding.contact_phone', '');
        
        $message = "Dear {$guestName},\n\n";
        $message .= "Thank you for choosing {$brandName}! We hope you enjoyed your stay with us.\n\n";
        $message .= "Booking Reference: {$reference}\n";
        $message .= "Check-in: {$checkIn}\n";
        $message .= "Check-out: {$checkOut}\n\n";
        $message .= "We would love to hear about your experience! Your feedback helps us improve our services.\n\n";
        
        if ($brandEmail || $brandPhone) {
            $message .= "If you have any questions or concerns, please don't hesitate to contact us:\n";
            if ($brandEmail) {
                $message .= "Email: {$brandEmail}\n";
            }
            if ($brandPhone) {
                $message .= "Phone: {$brandPhone}\n";
            }
            $message .= "\n";
        }
        
        $message .= "We look forward to welcoming you back soon!\n\n";
        $message .= "Best regards,\n";
        $message .= "{$brandName} Team";
        
        return $message;
    }

    /**
     * Send staff check-in confirmation email
     */
    public function sendStaffCheckInEmail(array $staff, array $attendance): bool
    {
        $data = [
            'title' => 'Check-in Confirmed',
            'subject' => 'Check-in Confirmed - ' . date('F j, Y'),
            'staff' => $staff,
            'attendance' => $attendance,
            'body' => $this->formatStaffCheckInMessage($staff, $attendance),
        ];
        
        return $this->sendTemplate($staff['email'] ?? '', 'staff-checkin', $data, $staff['name'] ?? null);
    }

    /**
     * Format staff check-in message
     */
    protected function formatStaffCheckInMessage(array $staff, array $attendance): string
    {
        $staffName = $staff['name'] ?? 'Staff Member';
        $checkInTime = $attendance['check_in_time'] ?? date('Y-m-d H:i:s');
        $brandName = settings('branding.name', 'Hotela');
        $brandEmail = settings('branding.contact_email', '');
        $brandPhone = settings('branding.contact_phone', '');
        
        $message = "Dear {$staffName},\n\n";
        $message .= "Your check-in has been confirmed.\n\n";
        $message .= "Check-in Time: " . date('l, F j, Y \a\t g:i A', strtotime($checkInTime)) . "\n";
        $message .= "Date: " . date('F j, Y', strtotime($checkInTime)) . "\n\n";
        
        if (!empty($attendance['notes'])) {
            $message .= "Notes: {$attendance['notes']}\n\n";
        }
        
        $message .= "You are now logged into the system and can access your dashboard.\n\n";
        
        if ($brandEmail || $brandPhone) {
            $message .= "If you have any questions, please contact:\n";
            if ($brandEmail) {
                $message .= "Email: {$brandEmail}\n";
            }
            if ($brandPhone) {
                $message .= "Phone: {$brandPhone}\n";
            }
            $message .= "\n";
        }
        
        $message .= "Have a productive day!\n\n";
        $message .= "Best regards,\n";
        $message .= "{$brandName} Team";
        
        return $message;
    }

    /**
     * Send notification email
     */
    public function sendNotification(string $to, string $title, string $message, ?string $toName = null): bool
    {
        $data = [
            'title' => $title,
            'subject' => $title,
            'body' => $message,
        ];
        
        return $this->sendTemplate($to, 'notification', $data, $toName);
    }

    /**
     * Send payment request email
     */
    public function sendPaymentRequest(string $to, string $customerName, string $orderRef, float $orderTotal, string $paymentLink, ?string $toName = null): bool
    {
        $brandName = settings('branding.name', 'Hotela');
        $brandEmail = settings('branding.contact_email', '');
        $brandPhone = settings('branding.contact_phone', '');
        
        $data = [
            'title' => 'Payment Request',
            'subject' => 'Payment Request - Order #' . $orderRef,
            'customerName' => $customerName,
            'orderRef' => $orderRef,
            'orderTotal' => number_format($orderTotal, 2),
            'paymentLink' => $paymentLink,
            'brandName' => $brandName,
            'brandEmail' => $brandEmail,
            'brandPhone' => $brandPhone,
        ];
        
        return $this->sendTemplate($to, 'payment-request', $data, $toName ?? $customerName);
    }

    /**
     * Send supplier login code email
     */
    public function sendSupplierLoginCode(string $to, string $supplierName, string $code): bool
    {
        $brandName = settings('branding.name', 'Hotela');
        $subject = "Your Supplier Portal Login Code";
        
        $body = "Dear {$supplierName},\n\n" .
               "Your login code for the Supplier Portal is:\n\n" .
               "<div style='font-size: 24px; font-weight: bold; text-align: center; padding: 20px; background: #f8fafc; border: 2px solid #8b5cf6; border-radius: 8px; margin: 20px 0;'>{$code}</div>\n\n" .
               "This code will expire in 15 minutes.\n\n" .
               "If you did not request this code, please ignore this email.\n\n" .
               "Best regards,\n" .
               "{$brandName} Team";
        
        return $this->send($to, $subject, $this->formatEmailBody($body, $subject), $supplierName);
    }
}

