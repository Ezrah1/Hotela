<?php
$pageTitle = 'Folio | ' . htmlspecialchars($reservation['guest_name']);
ob_start();
?>
<section class="card">
    <header class="booking-staff-header">
        <div>
            <h2>Folio: <?= htmlspecialchars($reservation['reference']); ?></h2>
            <p><?= htmlspecialchars($reservation['guest_name']); ?> · <?= htmlspecialchars($reservation['check_in']); ?> → <?= htmlspecialchars($reservation['check_out']); ?></p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('staff/dashboard/bookings'); ?>">Back to bookings</a>
    </header>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert danger"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert success">Entry added successfully.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['checkout_pending'])): ?>
        <?php
        // Use current folio balance instead of URL parameter (which may be stale)
        $currentBalance = (float)($folio['balance'] ?? 0);
        ?>
        <?php if ($currentBalance > 0): ?>
            <div class="alert warning">
                <strong>⚠️ Checkout Pending</strong>
                <p>Outstanding balance of <strong>KES <?= number_format($currentBalance, 2); ?></strong> must be settled before checkout.</p>
                <p>Please add a payment entry below to settle the balance, then you can complete checkout.</p>
            </div>
        <?php else: ?>
            <div class="alert success">
                <strong>✓ Balance Settled</strong>
                <p>The balance has been settled. You can now complete checkout.</p>
                <form method="post" action="<?= base_url('staff/dashboard/bookings/check-out'); ?>" style="margin-top: 1rem;">
                    <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                    <button type="submit" class="btn btn-primary">Complete Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($_GET['balance_settled'])): ?>
        <div class="alert success">
            <strong>✓ Balance Settled</strong>
            <p>The balance has been settled. You can now complete checkout.</p>
            <form method="post" action="<?= base_url('staff/dashboard/bookings/check-out'); ?>" style="margin-top: 1rem;">
                <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                <button type="submit" class="btn btn-primary">Complete Checkout</button>
            </form>
        </div>
    <?php endif; ?>
    <?php if (!empty($pendingPayments)): ?>
        <div class="alert info">
            <strong>⏳ Pending M-Pesa Payments</strong>
            <p>The following M-Pesa payments are pending confirmation:</p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <?php foreach ($pendingPayments as $payment): ?>
                    <li>
                        <strong>KES <?= number_format((float)$payment['amount'], 2); ?></strong> 
                        (Phone: <?= htmlspecialchars($payment['phone_number'] ?? 'N/A'); ?>)
                        - Requested: <?= htmlspecialchars($payment['created_at']); ?>
                        <form method="post" action="<?= base_url('staff/dashboard/bookings/folio-confirm-payment'); ?>" style="display: inline; margin-left: 0.5rem;">
                            <input type="hidden" name="transaction_id" value="<?= (int)$payment['id']; ?>">
                            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
                            <button type="submit" class="btn btn-small btn-outline" onclick="return confirm('Confirm this payment has been received? This will add it to the folio.');">
                                Confirm Payment
                            </button>
                        </form>
                        <button type="button" class="btn btn-small btn-outline" onclick="queryMpesaStatus('<?= htmlspecialchars($payment['checkout_request_id'] ?? ''); ?>', <?= (int)$payment['id']; ?>)">
                            Check Status
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <div class="folio-summary">
        <div>
            <p>Total Charges</p>
            <strong>KES <?= number_format($folio['total'], 2); ?></strong>
        </div>
        <div>
            <p>Balance</p>
            <strong><?= $folio['balance'] > 0 ? 'KES ' . number_format($folio['balance'], 2) : 'Settled'; ?></strong>
        </div>
        <div>
            <p>Status</p>
            <strong><?= htmlspecialchars($folio['status']); ?></strong>
        </div>
    </div>
    <table class="table-lite">
        <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Source</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= htmlspecialchars($entry['created_at']); ?></td>
                <td><?= htmlspecialchars($entry['description']); ?></td>
                <td><?= htmlspecialchars(ucfirst($entry['type'])); ?></td>
                <td><?= htmlspecialchars(number_format($entry['amount'], 2)); ?></td>
                <td><?= htmlspecialchars($entry['source'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="card">
    <h3>Add Entry</h3>
    <form class="folio-form" method="post" action="<?= base_url('staff/dashboard/bookings/folio-entry'); ?>">
        <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id']; ?>">
        <?php if (!empty($_GET['checkout_pending'])): ?>
            <input type="hidden" name="checkout_pending" value="1">
        <?php endif; ?>
        <label>
            <span>Description</span>
            <input type="text" name="description" id="description-input" required>
        </label>
        <label>
            <span>Type</span>
            <select name="type" id="type-select">
                <option value="charge">Charge</option>
                <option value="payment">Payment</option>
            </select>
        </label>
        <label>
            <span>Amount</span>
            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                <input type="number" step="0.01" name="amount" id="amount-input" required style="flex: 1;">
                <?php if ($folio['balance'] > 0): ?>
                    <button type="button" id="pay-full-balance-btn" class="btn btn-outline" style="white-space: nowrap; padding: 0.75rem 1rem; display: none;">
                        Pay Full Balance
                    </button>
                <?php endif; ?>
            </div>
            <?php if ($folio['balance'] > 0): ?>
                <small style="display: block; margin-top: 0.25rem; color: #64748b;">
                    Outstanding balance: <strong>KES <?= number_format($folio['balance'], 2); ?></strong>
                </small>
            <?php endif; ?>
        </label>
        <label>
            <span>Payment Method</span>
            <select name="source" id="payment-method-select">
                <option value="cash">Cash</option>
                <option value="mpesa">M-Pesa</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="room">Room Charge</option>
                <option value="corporate">Corporate</option>
            </select>
            <small style="display: block; margin-top: 0.25rem; color: #64748b;">
                Select payment method. Cash payments will be included in daily cash banking.
            </small>
        </label>
        <div id="mpesa-phone-field" style="display: none;">
            <label>
                <span>M-Pesa Phone Number</span>
                <input type="text" name="mpesa_phone" id="mpesa-phone-input" placeholder="254700000000" pattern="[0-9]+" maxlength="12">
                <small style="display: block; margin-top: 0.25rem; color: #64748b;">
                    Enter phone number (e.g., 254700000000 or 0700000000)
                </small>
            </label>
        </div>
        <div class="form-actions-folio">
            <button class="btn btn-primary" type="submit" id="submit-entry-btn">Add Entry</button>
            <button type="button" class="btn btn-outline" id="initiate-mpesa-btn" style="display: none;">Initiate M-Pesa Payment</button>
        </div>
    </form>
</section>
<style>
.alert.warning {
    background: #fef3c7;
    border: 1px solid #fde68a;
    color: #92400e;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.warning strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #78350f;
}

.alert.warning p {
    margin: 0.5rem 0;
}

.alert.warning p:last-child {
    margin-bottom: 0;
}

.alert.info {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1e40af;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert.info strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #1e3a8a;
}

.alert.info ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.alert.info li {
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.form-actions-folio {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

#mpesa-phone-field {
    margin-top: 1rem;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payFullBalanceBtn = document.getElementById('pay-full-balance-btn');
    const amountInput = document.getElementById('amount-input');
    const descriptionInput = document.getElementById('description-input');
    const typeSelect = document.getElementById('type-select');
    const balance = <?= $folio['balance'] ?? 0; ?>;

    if (payFullBalanceBtn && balance > 0) {
        payFullBalanceBtn.addEventListener('click', function() {
            // Set amount to full balance
            amountInput.value = balance.toFixed(2);
            
            // Set type to payment
            typeSelect.value = 'payment';
            
            // Auto-fill description if empty
            if (!descriptionInput.value || descriptionInput.value.trim() === '') {
                descriptionInput.value = 'Payment - Full Balance Settlement';
            }
            
            // Focus on description so user can edit if needed
            descriptionInput.focus();
        });
    }

    // Show/hide "Pay Full Balance" button based on type selection
    if (typeSelect && payFullBalanceBtn) {
        function togglePayFullButton() {
            if (typeSelect.value === 'payment' && balance > 0) {
                payFullBalanceBtn.style.display = 'inline-block';
            } else {
                payFullBalanceBtn.style.display = 'none';
            }
        }
        
        typeSelect.addEventListener('change', togglePayFullButton);
        
        // Initial state
        togglePayFullButton();
    }

    // Handle M-Pesa payment method selection
    const paymentMethodSelect = document.getElementById('payment-method-select');
    const mpesaPhoneField = document.getElementById('mpesa-phone-field');
    const mpesaPhoneInput = document.getElementById('mpesa-phone-input');
    const submitEntryBtn = document.getElementById('submit-entry-btn');
    const initiateMpesaBtn = document.getElementById('initiate-mpesa-btn');
    const form = document.querySelector('.folio-form');

    if (paymentMethodSelect && mpesaPhoneField) {
        paymentMethodSelect.addEventListener('change', function() {
            if (this.value === 'mpesa' && typeSelect.value === 'payment') {
                mpesaPhoneField.style.display = 'block';
                mpesaPhoneInput.required = true;
                submitEntryBtn.style.display = 'none';
                initiateMpesaBtn.style.display = 'inline-block';
            } else {
                mpesaPhoneField.style.display = 'none';
                mpesaPhoneInput.required = false;
                submitEntryBtn.style.display = 'inline-block';
                initiateMpesaBtn.style.display = 'none';
            }
        });

        // Also check when type changes
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                if (paymentMethodSelect.value === 'mpesa' && this.value === 'payment') {
                    mpesaPhoneField.style.display = 'block';
                    mpesaPhoneInput.required = true;
                    submitEntryBtn.style.display = 'none';
                    initiateMpesaBtn.style.display = 'inline-block';
                } else {
                    mpesaPhoneField.style.display = 'none';
                    mpesaPhoneInput.required = false;
                    submitEntryBtn.style.display = 'inline-block';
                    initiateMpesaBtn.style.display = 'none';
                }
            });
        }

        // Handle M-Pesa payment initiation
        if (initiateMpesaBtn) {
            initiateMpesaBtn.addEventListener('click', async function() {
                const amount = parseFloat(amountInput.value);
                const phone = mpesaPhoneInput.value.trim();
                const description = descriptionInput.value.trim() || 'Folio Payment';
                const reservationId = <?= (int)$reservation['id']; ?>;

                if (!phone) {
                    alert('Please enter M-Pesa phone number');
                    mpesaPhoneInput.focus();
                    return;
                }

                if (amount <= 0) {
                    alert('Please enter a valid amount');
                    amountInput.focus();
                    return;
                }

                // Disable button during processing
                initiateMpesaBtn.disabled = true;
                initiateMpesaBtn.textContent = 'Processing...';

                try {
                    const response = await fetch('<?= base_url('staff/dashboard/bookings/folio-mpesa-payment'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reservation_id: reservationId,
                            amount: amount,
                            phone: phone,
                            description: description,
                            checkout_pending: <?= !empty($_GET['checkout_pending']) ? 'true' : 'false'; ?>
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('M-Pesa payment request sent! Please check your phone and enter your M-Pesa PIN to complete the payment.');
                        // Reload page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to initiate M-Pesa payment'));
                        initiateMpesaBtn.disabled = false;
                        initiateMpesaBtn.textContent = 'Initiate M-Pesa Payment';
                    }
                } catch (error) {
                    alert('Network error: ' + error.message);
                    initiateMpesaBtn.disabled = false;
                    initiateMpesaBtn.textContent = 'Initiate M-Pesa Payment';
                }
            });
        }
    }

    // Query M-Pesa payment status
    window.queryMpesaStatus = async function(checkoutRequestId, transactionId) {
        if (!checkoutRequestId) {
            alert('No checkout request ID available');
            return;
        }
        
        try {
            const response = await fetch('<?= base_url('staff/dashboard/bookings/folio-query-payment'); ?>?checkout_request_id=' + encodeURIComponent(checkoutRequestId) + '&transaction_id=' + transactionId);
            const result = await response.json();
            
            if (result.success) {
                if (result.status === 'completed') {
                    alert('Payment confirmed! The payment has been added to the folio.');
                    window.location.reload();
                } else {
                    alert('Payment status: ' + (result.data?.result_desc || 'Still pending. Please wait for M-Pesa callback or confirm manually.'));
                }
            } else {
                alert('Error: ' + (result.message || 'Failed to query payment status'));
            }
        } catch (error) {
            alert('Network error: ' + error.message);
        }
    };
});
</script>
<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');

