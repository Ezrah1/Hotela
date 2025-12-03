<?php
$reservation = $reservation ?? [];
$roomType = $roomType ?? [];
$room = $room ?? [];
$branding = settings('branding', []);
$businessName = $branding['name'] ?? 'Hotela';
$businessAddress = $branding['address'] ?? '';
$businessPhone = $branding['contact_phone'] ?? '';
$businessEmail = $branding['contact_email'] ?? '';

$paymentStatus = $reservation['payment_status'] ?? 'unpaid';
$paymentMethod = $reservation['payment_method'] ?? 'pay_on_arrival';
$mpesaStatus = $reservation['mpesa_status'] ?? null;
$isMpesaPending = $paymentMethod === 'mpesa' && ($mpesaStatus === 'pending' || $paymentStatus === 'unpaid');
$isMpesaFailed = $paymentMethod === 'mpesa' && ($mpesaStatus === 'failed' || $mpesaStatus === 'cancelled');
$isMpesaPaid = $paymentMethod === 'mpesa' && ($mpesaStatus === 'completed' || $paymentStatus === 'paid');

$checkIn = new DateTimeImmutable($reservation['check_in'] ?? date('Y-m-d'));
$checkOut = new DateTimeImmutable($reservation['check_out'] ?? date('Y-m-d'));
$nights = max(1, $checkIn->diff($checkOut)->days);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - <?= htmlspecialchars($businessName); ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/main.css'); ?>">
    <style>
        body {
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .header h1 {
            margin: 0 0 0.5rem 0;
            color: #1e293b;
            font-size: 2rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .detail-card {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
        }
        .detail-label {
            display: block;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .detail-value {
            display: block;
            color: #1e293b;
            font-size: 1.125rem;
            font-weight: 600;
        }
        .payment-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .payment-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        .btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-outline {
            background: white;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        .btn-outline:hover {
            background: #eff6ff;
        }
        @media print {
            .actions, .btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="header">
            <h1>Booking Confirmed!</h1>
            <p style="color: #64748b; margin: 0.5rem 0;">Thank you for your reservation</p>
            <?php if ($isMpesaPending): ?>
                <div class="status-badge status-pending">⏳ Payment Pending</div>
            <?php elseif ($isMpesaPaid): ?>
                <div class="status-badge status-paid">✓ Payment Confirmed</div>
            <?php elseif ($isMpesaFailed): ?>
                <div class="status-badge status-failed">✗ Payment Failed</div>
            <?php elseif ($paymentMethod === 'pay_on_arrival'): ?>
                <div class="status-badge status-pending">Pay on Arrival</div>
            <?php endif; ?>
        </div>

        <div class="booking-details">
            <div class="detail-card">
                <span class="detail-label">Booking Reference</span>
                <span class="detail-value"><?= htmlspecialchars($reservation['reference'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-card">
                <span class="detail-label">Guest Name</span>
                <span class="detail-value"><?= htmlspecialchars($reservation['guest_name'] ?? 'Guest'); ?></span>
            </div>
            <div class="detail-card">
                <span class="detail-label">Check-in Date</span>
                <span class="detail-value"><?= $checkIn->format('F j, Y'); ?></span>
            </div>
            <div class="detail-card">
                <span class="detail-label">Check-out Date</span>
                <span class="detail-value"><?= $checkOut->format('F j, Y'); ?></span>
            </div>
            <div class="detail-card">
                <span class="detail-label">Nights</span>
                <span class="detail-value"><?= $nights; ?> <?= $nights === 1 ? 'Night' : 'Nights'; ?></span>
            </div>
            <div class="detail-card">
                <span class="detail-label">Room Type</span>
                <span class="detail-value"><?= htmlspecialchars($roomType['name'] ?? 'Room Type'); ?></span>
            </div>
            <?php if ($room): ?>
            <div class="detail-card">
                <span class="detail-label">Assigned Room</span>
                <span class="detail-value"><?= htmlspecialchars($room['room_number'] ?? $room['display_name'] ?? 'N/A'); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-card">
                <span class="detail-label">Guests</span>
                <span class="detail-value"><?= (int)($reservation['adults'] ?? 1); ?> Adults, <?= (int)($reservation['children'] ?? 0); ?> Children</span>
            </div>
        </div>

        <div class="payment-section">
            <h3 style="margin: 0 0 1rem 0; color: #1e293b;">Payment Information</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b;">Payment Method:</span>
                <span style="color: #1e293b; font-weight: 600; text-transform: uppercase;">
                    <?php
                    if ($paymentMethod === 'mpesa') {
                        echo 'M-Pesa';
                        if (!empty($reservation['mpesa_phone'])) {
                            echo ' (' . htmlspecialchars($reservation['mpesa_phone']) . ')';
                        }
                    } else {
                        echo 'Pay on Arrival';
                    }
                    ?>
                </span>
            </div>
            <?php if ($isMpesaPending): ?>
                <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px; padding: 0.75rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                        <strong>⏳ Payment Pending:</strong> M-Pesa payment is being processed. Please check your phone to complete the payment.
                    </p>
                </div>
            <?php elseif ($isMpesaFailed): ?>
                <div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 6px; padding: 0.75rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #991b1b; font-size: 0.875rem;">
                        <strong>✗ Payment <?= $mpesaStatus === 'cancelled' ? 'Cancelled' : 'Failed'; ?>:</strong> The M-Pesa payment was <?= $mpesaStatus === 'cancelled' ? 'cancelled' : 'not completed'; ?>. Please contact us or try again.
                    </p>
                </div>
            <?php elseif ($isMpesaPaid): ?>
                <div style="background: #d1fae5; border: 1px solid #10b981; border-radius: 6px; padding: 0.75rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #065f46; font-size: 0.875rem;">
                        <strong>✓ Payment Confirmed</strong>
                        <?php if (!empty($reservation['mpesa_transaction_id'])): ?>
                            <br><small>Transaction ID: <?= htmlspecialchars($reservation['mpesa_transaction_id']); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            <div class="payment-info">
                <span style="color: #64748b; font-size: 1.125rem;">Total Amount:</span>
                <span class="total-amount">KES <?= number_format((float)($reservation['total_amount'] ?? 0), 2); ?></span>
            </div>
        </div>

        <?php if (!empty($reservation['extras'])): 
            $extras = json_decode($reservation['extras'], true);
            if (!empty($extras['special_requests'])): ?>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin: 2rem 0;">
                    <h3 style="margin: 0 0 1rem 0; color: #1e293b;">Special Requests</h3>
                    <p style="margin: 0; color: #64748b;"><?= nl2br(htmlspecialchars($extras['special_requests'])); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; text-align: center; color: #64748b; font-size: 0.875rem;">
            <p style="margin: 0.5rem 0;"><strong><?= htmlspecialchars($businessName); ?></strong></p>
            <?php if ($businessAddress): ?>
                <p style="margin: 0.5rem 0;"><?= htmlspecialchars($businessAddress); ?></p>
            <?php endif; ?>
            <?php if ($businessPhone || $businessEmail): ?>
                <p style="margin: 0.5rem 0;">
                    <?php if ($businessPhone): ?>
                        Tel: <?= htmlspecialchars($businessPhone); ?>
                    <?php endif; ?>
                    <?php if ($businessPhone && $businessEmail): ?> | <?php endif; ?>
                    <?php if ($businessEmail): ?>
                        Email: <?= htmlspecialchars($businessEmail); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="<?= base_url('booking/confirmation?reference=' . urlencode($reservation['reference'] ?? '') . '&print=1'); ?>" class="btn btn-primary" target="_blank">Print Receipt</a>
            <a href="<?= base_url('guest/booking?ref=' . urlencode($reservation['reference'] ?? '') . '&download=receipt'); ?>" class="btn btn-primary" target="_blank">Download Receipt</a>
            <a href="<?= base_url('guest/portal'); ?>" class="btn btn-outline">View My Bookings</a>
        </div>
    </div>
    
    <?php if ($isMpesaPending): ?>
    <script>
        // Real-time payment status polling for M-Pesa payments
        (function() {
            const reference = '<?= htmlspecialchars($reservation['reference'] ?? ''); ?>';
            if (!reference) return;
            
            let pollCount = 0;
            const maxPolls = 60; // Poll for up to 5 minutes (60 * 5 seconds)
            let pollInterval;
            
            function checkPaymentStatus() {
                pollCount++;
                
                if (pollCount > maxPolls) {
                    clearInterval(pollInterval);
                    return;
                }
                
                fetch('<?= base_url('api/booking/payment-status'); ?>?reference=' + encodeURIComponent(reference))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const paymentStatus = data.payment_status;
                            const mpesaStatus = data.mpesa_status;
                            const bookingStatus = data.booking_status;
                            
                            // Check if payment status changed
                            if (paymentStatus === 'paid' || mpesaStatus === 'completed') {
                                // Payment confirmed - reload page to show updated status
                                clearInterval(pollInterval);
                                window.location.reload();
                            } else if (mpesaStatus === 'failed' || mpesaStatus === 'cancelled') {
                                // Payment failed - reload page to show updated status
                                clearInterval(pollInterval);
                                window.location.reload();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error checking payment status:', error);
                    });
            }
            
            // Start polling every 5 seconds
            pollInterval = setInterval(checkPaymentStatus, 5000);
            
            // Also check immediately
            checkPaymentStatus();
        })();
    </script>
    <?php endif; ?>
</body>
</html>

