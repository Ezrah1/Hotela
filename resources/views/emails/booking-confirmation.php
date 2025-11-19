<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Booking Confirmation'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .content {
            padding: 2rem;
            background: #ffffff;
        }
        .booking-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        .booking-details h2 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            color: #0f172a;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
        }
        .message {
            color: #475569;
            line-height: 1.7;
            margin: 1.5rem 0;
        }
        .footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Booking Confirmed!</h1>
        </div>
        <div class="content">
            <p class="message">
                Dear <?= htmlspecialchars($guest['name'] ?? $guest['guest_name'] ?? 'Guest'); ?>,
            </p>
            <p class="message">
                Thank you for your booking! Your reservation has been confirmed. We look forward to welcoming you.
            </p>
            
            <div class="booking-details">
                <h2>Booking Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['reference'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['check_in'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['check_out'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['room_type_name'] ?? 'N/A'); ?></span>
                </div>
                <?php if (!empty($booking['room_label']) && !empty($booking['room_id'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Room:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['room_label']); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Guests:</span>
                    <span class="detail-value">
                        <?= (int)($booking['adults'] ?? 1); ?> Adult<?= (int)($booking['adults'] ?? 1) !== 1 ? 's' : ''; ?>
                        <?php if (!empty($booking['children']) && (int)$booking['children'] > 0): ?>
                            , <?= (int)$booking['children']; ?> Child<?= (int)$booking['children'] !== 1 ? 'ren' : ''; ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($booking['nights'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Nights:</span>
                    <span class="detail-value"><?= (int)$booking['nights']; ?> Night<?= (int)$booking['nights'] !== 1 ? 's' : ''; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($booking['total_amount'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">KES <?= number_format($booking['total_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($booking['special_requests'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Special Requests:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['special_requests']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <p class="message">
                If you have any questions or need to make changes to your booking, please contact us.
            </p>
            <p class="message">
                Best regards,<br>
                <strong><?= htmlspecialchars(settings('branding.name', 'Hotela')); ?> Team</strong>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated message from <?= htmlspecialchars(settings('branding.name', 'Hotela')); ?>.</p>
            <p>Please do not reply to this email.</p>
            <?php if (!empty(settings('branding.contact_email'))): ?>
            <p>For inquiries, contact us at: <?= htmlspecialchars(settings('branding.contact_email')); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

