<?php
$brandName = $brandName ?? settings('branding.name', 'Hotela');
$brandEmail = $brandEmail ?? settings('branding.contact_email', '');
$brandPhone = $brandPhone ?? settings('branding.contact_phone', '');
$customerName = $customerName ?? 'Customer';
$orderRef = $orderRef ?? '';
$orderTotal = $orderTotal ?? '0.00';
$paymentLink = $paymentLink ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request - Order #<?= htmlspecialchars($orderRef); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .order-details {
            background-color: #f8fafc;
            border-left: 4px solid #8b5cf6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .order-details h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #1e293b;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-size: 14px;
        }
        .detail-value {
            color: #1e293b;
            font-size: 14px;
            font-weight: 600;
        }
        .payment-button {
            display: block;
            text-align: center;
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .payment-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        .payment-link-fallback {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 14px;
            color: #475569;
        }
        .payment-link-fallback strong {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .payment-link-fallback a {
            color: #8b5cf6;
            text-decoration: none;
        }
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #64748b;
        }
        .footer a {
            color: #8b5cf6;
            text-decoration: none;
        }
        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .contact-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Payment Request</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Dear <?= htmlspecialchars($customerName); ?>,
            </div>
            
            <div class="message">
                We have a pending payment for your order. Please complete your payment to proceed with your order.
            </div>
            
            <div class="order-details">
                <h3>Order Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Order Reference:</span>
                    <span class="detail-value"><?= htmlspecialchars($orderRef); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">KES <?= htmlspecialchars($orderTotal); ?></span>
                </div>
            </div>
            
            <a href="<?= htmlspecialchars($paymentLink); ?>" class="payment-button">
                Complete Payment Now
            </a>
            
            <div class="payment-link-fallback">
                <strong>Or copy and paste this link into your browser:</strong>
                <a href="<?= htmlspecialchars($paymentLink); ?>"><?= htmlspecialchars($paymentLink); ?></a>
            </div>
            
            <div class="message" style="margin-top: 30px; font-size: 14px; color: #64748b;">
                If you have any questions or need assistance, please don't hesitate to contact us.
            </div>
        </div>
        
        <div class="footer">
            <p><strong><?= htmlspecialchars($brandName); ?></strong></p>
            <?php if ($brandEmail || $brandPhone): ?>
                <div class="contact-info">
                    <?php if ($brandEmail): ?>
                        <p>Email: <a href="mailto:<?= htmlspecialchars($brandEmail); ?>"><?= htmlspecialchars($brandEmail); ?></a></p>
                    <?php endif; ?>
                    <?php if ($brandPhone): ?>
                        <p>Phone: <a href="tel:<?= htmlspecialchars($brandPhone); ?>"><?= htmlspecialchars($brandPhone); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <p style="margin-top: 20px; font-size: 12px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>

