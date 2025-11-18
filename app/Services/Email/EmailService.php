<?php

namespace App\Services\Email;

use App\Services\Settings\SettingStore;

// Check if PHPMailer is available
if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    // Try to load PHPMailer from vendor directory
    $phpmailerPath = BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';
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
            
            $fromEmail = $notifications['default_from_email'] ?? 'noreply@hotela.local';
            $fromName = $notifications['default_from_name'] ?? 'Hotela';
            
            $mailer->setFrom($fromEmail, $fromName);
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
}

