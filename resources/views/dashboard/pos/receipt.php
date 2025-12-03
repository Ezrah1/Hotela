<?php
$sale = $sale ?? [];
$items = $items ?? [];
$user = $user ?? [];
ob_start();
$branding = settings('branding', []);
$businessName = $branding['name'] ?? 'Hotela';
$businessAddress = $branding['address'] ?? '';
$businessPhone = $branding['contact_phone'] ?? '';
$businessEmail = $branding['contact_email'] ?? '';
$paymentStatus = $sale['payment_status'] ?? 'pending';
$mpesaStatus = $sale['mpesa_status'] ?? null;
$paymentType = $sale['payment_type'] ?? 'cash';
$isMpesaPending = $paymentType === 'mpesa' && ($mpesaStatus === 'pending' || $paymentStatus === 'pending');
$isMpesaFailed = $paymentType === 'mpesa' && ($mpesaStatus === 'failed' || $paymentStatus === 'failed');
$isMpesaCancelled = $paymentType === 'mpesa' && ($mpesaStatus === 'cancelled' || $paymentStatus === 'cancelled');
?>
<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 2rem;">
        <!-- Receipt Header -->
        <div style="text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <h1 style="margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.75rem;"><?= htmlspecialchars($businessName); ?></h1>
            <?php if ($businessAddress): ?>
                <p style="margin: 0.25rem 0; color: #64748b; font-size: 0.875rem;"><?= htmlspecialchars($businessAddress); ?></p>
            <?php endif; ?>
            <?php if ($businessPhone || $businessEmail): ?>
                <p style="margin: 0.25rem 0; color: #64748b; font-size: 0.875rem;">
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

        <!-- Receipt Details -->
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Receipt #:</span>
                <span style="color: #1e293b; font-weight: 600;"><?= htmlspecialchars($sale['reference'] ?? 'N/A'); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Date:</span>
                <span style="color: #1e293b;"><?= date('F j, Y g:i A', strtotime($sale['created_at'] ?? 'now')); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Staff:</span>
                <span style="color: #1e293b;"><?= htmlspecialchars($sale['user_name'] ?? $user['name'] ?? 'Staff'); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Payment Method:</span>
                <span style="color: #1e293b; text-transform: uppercase;">
                    <?php
                    $paymentType = $sale['payment_type'] ?? 'cash';
                    echo htmlspecialchars($paymentType);
                    if ($paymentType === 'mpesa' && !empty($sale['mpesa_phone'])) {
                        echo ' (' . htmlspecialchars($sale['mpesa_phone']) . ')';
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
            <?php elseif ($isMpesaFailed || $isMpesaCancelled): ?>
                <div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 6px; padding: 0.75rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #991b1b; font-size: 0.875rem;">
                        <strong>✗ Payment <?= $isMpesaCancelled ? 'Cancelled' : 'Failed'; ?>:</strong> The M-Pesa payment was <?= $isMpesaCancelled ? 'cancelled' : 'not completed'; ?>. Please try again or use a different payment method.
                    </p>
                </div>
            <?php elseif ($paymentStatus === 'paid'): ?>
                <div style="background: #d1fae5; border: 1px solid #10b981; border-radius: 6px; padding: 0.75rem; margin-top: 1rem;">
                    <p style="margin: 0; color: #065f46; font-size: 0.875rem;">
                        <strong>✓ Payment Confirmed</strong>
                        <?php if (!empty($sale['mpesa_transaction_id'])): ?>
                            <br><small>Transaction ID: <?= htmlspecialchars($sale['mpesa_transaction_id']); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0;">
                    <th style="text-align: left; padding: 0.75rem 0; color: #64748b; font-weight: 600; font-size: 0.875rem;">Item</th>
                    <th style="text-align: center; padding: 0.75rem 0; color: #64748b; font-weight: 600; font-size: 0.875rem;">Qty</th>
                    <th style="text-align: right; padding: 0.75rem 0; color: #64748b; font-weight: 600; font-size: 0.875rem;">Price</th>
                    <th style="text-align: right; padding: 0.75rem 0; color: #64748b; font-weight: 600; font-size: 0.875rem;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 0; color: #1e293b;"><?= htmlspecialchars($item['item_name'] ?? 'Item'); ?></td>
                        <td style="text-align: center; padding: 0.75rem 0; color: #64748b;"><?= (int)($item['quantity'] ?? 0); ?></td>
                        <td style="text-align: right; padding: 0.75rem 0; color: #64748b;">KES <?= number_format((float)($item['price'] ?? 0), 2); ?></td>
                        <td style="text-align: right; padding: 0.75rem 0; color: #1e293b; font-weight: 500;">KES <?= number_format((float)($item['line_total'] ?? 0), 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div style="border-top: 2px solid #e2e8f0; padding-top: 1rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Subtotal:</span>
                <span style="color: #1e293b; font-weight: 500;">KES <?= number_format((float)($sale['total'] ?? 0), 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #64748b; font-weight: 500;">Tax:</span>
                <span style="color: #1e293b; font-weight: 500;">KES 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; margin-top: 0.5rem;">
                <span style="color: #1e293b; font-weight: 700; font-size: 1.125rem;">Total:</span>
                <span style="color: #1e293b; font-weight: 700; font-size: 1.125rem;">KES <?= number_format((float)($sale['total'] ?? 0), 2); ?></span>
            </div>
        </div>

        <!-- QR Code Section -->
        <?php
        // Build QR code URL - link to online receipt
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $receiptPath = base_url('staff/dashboard/pos/receipt?id=' . (int)($sale['id'] ?? 0));
        $receiptUrl = $scheme . '://' . $host . $receiptPath;
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=1&data=' . urlencode($receiptUrl);
        ?>
        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
            <p style="margin-bottom: 0.75rem; font-size: 0.9rem; color: #64748b; font-weight: 500;">Scan to view receipt online</p>
            <div style="display: inline-block; padding: 1rem; background: white; border: 2px solid #e2e8f0; border-radius: 8px;">
                <img src="<?= htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code - Receipt" style="width: 200px; height: 200px; display: block; max-width: 100%;">
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 0.875rem;">
            <p style="margin: 0.5rem 0;">Thank you for your business!</p>
            <p style="margin: 0.5rem 0;"><?= htmlspecialchars($businessName); ?> - <?= date('Y'); ?></p>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
            <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">Print Receipt</button>
            <a href="<?= base_url('staff/dashboard/pos'); ?>" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none; display: inline-block;">New Sale</a>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, a.btn {
        display: none !important;
    }
    body {
        background: white;
    }
    .card {
        box-shadow: none;
        border: none;
    }
}
</style>
<?php
$slot = ob_get_clean();
$pageTitle = 'Receipt - ' . ($sale['reference'] ?? 'POS Sale');
include view_path('layouts/dashboard.php');
?>

