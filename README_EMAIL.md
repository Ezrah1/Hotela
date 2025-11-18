# Email System Setup

## Overview

The Hotela email system uses PHPMailer to send emails via SMTP or PHP's mail() function.

## Installation

### 1. Install PHPMailer via Composer

```bash
composer require phpmailer/phpmailer
```

Or manually download from: https://github.com/PHPMailer/PHPMailer

### 2. Configure SMTP Settings

Go to **Admin → Settings → Integrations** and configure:

- **SMTP Host**: Your SMTP server (e.g., `smtp.gmail.com`, `smtp.mailtrap.io`)
- **SMTP Port**: Usually `587` for TLS or `465` for SSL
- **SMTP Username**: Your email address
- **SMTP Password**: Your email password or app-specific password
- **SMTP Encryption**: TLS (recommended) or SSL
- **Enable SMTP Authentication**: Check this box

### 3. Configure Email Settings

Go to **Admin → Settings → Notifications** and configure:

- **Enable email notifications**: Check to enable email sending
- **From Email Address**: The sender email address
- **From Name**: The sender name (e.g., "Hotela")

## Common SMTP Providers

### Gmail

- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: `TLS`
- Username: Your Gmail address
- Password: App-specific password (not your regular password)

### Mailtrap (Testing)

- Host: `smtp.mailtrap.io`
- Port: `2525`
- Encryption: `TLS`
- Username/Password: From your Mailtrap inbox settings

### SendGrid

- Host: `smtp.sendgrid.net`
- Port: `587`
- Encryption: `TLS`
- Username: `apikey`
- Password: Your SendGrid API key

### Mailgun

- Host: `smtp.mailgun.org`
- Port: `587`
- Encryption: `TLS`
- Username: Your Mailgun SMTP username
- Password: Your Mailgun SMTP password

## Usage

### Send a Simple Email

```php
use App\Services\Email\EmailService;

$email = new EmailService();
$email->send(
    'user@example.com',
    'Subject',
    'Email body content',
    'User Name',
    true // is HTML
);
```

### Send Booking Confirmation

```php
$email = new EmailService();
$email->sendBookingConfirmation($bookingData, $guestData);
```

### Send Notification Email

```php
$email = new EmailService();
$email->sendNotification(
    'user@example.com',
    'Notification Title',
    'Notification message',
    'User Name'
);
```

## Email Templates

Email templates are located in `resources/views/emails/`:

- `booking-confirmation.php` - Booking confirmation emails
- `notification.php` - General notification emails

You can create custom templates and use them with:

```php
$email->sendTemplate('user@example.com', 'template-name', $data);
```

## Testing

1. Use Mailtrap for testing (catches all emails)
2. Check error logs if emails aren't sending
3. Enable debug mode in `.env`: `APP_DEBUG=true`

## Troubleshooting

- **Emails not sending**: Check SMTP credentials and firewall
- **Authentication failed**: Verify username/password
- **Connection timeout**: Check SMTP host and port
- **Check error logs**: Look in PHP error log for SMTP debug messages
