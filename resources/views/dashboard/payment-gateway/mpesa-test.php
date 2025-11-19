<?php
$verification = $verification ?? ['valid' => false, 'errors' => []];
ob_start();
?>

<div class="page-header">
    <h1>M-Pesa Sandbox Test</h1>
    <p>Test M-Pesa payment integration using the sandbox environment</p>
</div>

<?php if (!$verification['valid']): ?>
    <div class="alert alert-warning">
        <h3>⚠️ Configuration Required</h3>
        <p>Please configure M-Pesa settings before testing:</p>
        <ul>
            <?php foreach ($verification['errors'] as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= base_url('staff/admin/settings?tab=payment-gateway&gateway=mpesa'); ?>" class="btn btn-primary">
            Configure M-Pesa Settings
        </a>
    </div>
<?php else: ?>
    <div class="test-container">
        <div class="test-info">
            <div class="info-card">
                <h3>📋 Test Information</h3>
                <p><strong>Environment:</strong> <?= strtoupper($verification['environment']); ?></p>
                <p><strong>Test Phone Numbers:</strong> 254700000000 - 254700000009</p>
                <p><strong>Test PIN:</strong> 1234</p>
                <p class="note">Use these test credentials from Safaricom Developer Portal to simulate payments.</p>
            </div>
            <div class="info-card">
                <h3>🔗 Callback URL</h3>
                <p><strong>Your Callback URL (will be sent to M-Pesa):</strong></p>
                <?php
                // Get the actual callback URL that will be used
                $actualCallbackUrl = $verification['callback_url'] ?? 'https://hotela.ezrahkiilu.com/api/mpesa/callback';
                ?>
                <div class="callback-url-display">
                    <code id="callbackUrl"><?= htmlspecialchars($actualCallbackUrl); ?></code>
                    <button type="button" class="btn-copy" onclick="copyCallbackUrl()" title="Copy to clipboard">
                        📋 Copy
                    </button>
                </div>
                <p class="note" style="margin-top: 1rem;">
                    <strong>⚠️ Important:</strong><br>
                    • For local testing, use <strong>ngrok</strong> or similar to expose this URL<br>
                    • M-Pesa requires a publicly accessible HTTPS URL<br>
                    • Update this URL in your Safaricom Developer Portal if needed
                </p>
            </div>
        </div>

        <div class="test-form-card">
            <h2>Initiate STK Push Test</h2>
            <form id="stkPushForm" class="test-form">
                <div class="form-group">
                    <label>
                        <span>Phone Number</span>
                        <input type="text" name="phone_number" id="phone_number" 
                               placeholder="254700000000" 
                               value="254700000000"
                               required>
                        <small>Use test numbers: 254700000000 to 254700000009</small>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Amount (KES)</span>
                        <input type="number" name="amount" id="amount" 
                               placeholder="100" 
                               min="1" 
                               step="0.01"
                               value="100"
                               required>
                        <small>Minimum: 1 KES</small>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Account Reference</span>
                        <input type="text" name="account_reference" id="account_reference" 
                               placeholder="TEST-001" 
                               value="TEST-<?= time(); ?>">
                        <small>Reference for this transaction</small>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <span>Transaction Description</span>
                        <input type="text" name="transaction_desc" id="transaction_desc" 
                               placeholder="M-Pesa Sandbox Test" 
                               value="M-Pesa Sandbox Test">
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="btn-text">Initiate STK Push</span>
                        <span class="btn-loading" style="display: none;">Processing...</span>
                    </button>
                </div>
            </form>
        </div>

        <div id="resultCard" class="result-card" style="display: none;">
            <h3>Test Result</h3>
            <div id="resultContent"></div>
            <div id="statusQuerySection" style="display: none; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" id="queryStatusBtn">
                    Query Payment Status
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.test-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.alert {
    padding: 1.5rem;
    border-radius: 8px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
}

.alert h3 {
    margin-top: 0;
    color: #856404;
}

.alert ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.test-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info-card h3 {
    margin-top: 0;
    color: #495057;
}

.info-card p {
    margin: 0.5rem 0;
    color: #6c757d;
}

.info-card p strong {
    color: #495057;
}

.info-card .note {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    font-style: italic;
    font-size: 0.9rem;
}

.test-form-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.test-form-card h2 {
    margin-top: 0;
    color: #495057;
    margin-bottom: 1.5rem;
}

.test-form .form-group {
    margin-bottom: 1.5rem;
}

.test-form label {
    display: block;
    margin-bottom: 0.5rem;
}

.test-form label span {
    display: block;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.test-form input[type="text"],
.test-form input[type="number"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.test-form input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.test-form small {
    display: block;
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.85rem;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    font-size: 0.95rem;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #0056b3;
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.result-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.result-card h3 {
    margin-top: 0;
    color: #495057;
}

.result-success {
    padding: 1rem;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
    color: #155724;
}

.result-error {
    padding: 1rem;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    color: #721c24;
}

.result-info {
    padding: 1rem;
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 6px;
    color: #0c5460;
}

.result-data {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.85rem;
    overflow-x: auto;
}

.result-data pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.callback-url-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.75rem 0;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.callback-url-display code {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    color: #007bff;
    word-break: break-all;
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
}

.btn-copy {
    padding: 0.5rem 1rem;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-copy:hover {
    background: #0056b3;
}

.btn-copy:active {
    background: #004085;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('stkPushForm');
    const resultCard = document.getElementById('resultCard');
    const resultContent = document.getElementById('resultContent');
    const submitBtn = document.getElementById('submitBtn');
    const statusQuerySection = document.getElementById('statusQuerySection');
    const queryStatusBtn = document.getElementById('queryStatusBtn');
    let checkoutRequestId = null;

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text').style.display = 'none';
            submitBtn.querySelector('.btn-loading').style.display = 'inline';
            
            resultCard.style.display = 'none';
            
            try {
                const response = await fetch('<?= base_url('staff/dashboard/payment-gateway/mpesa-test/stk-push'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    checkoutRequestId = result.data?.checkout_request_id || null;
                    
                    resultContent.innerHTML = `
                        <div class="result-success">
                            <strong>✓ Success!</strong>
                            <p>${result.message || 'STK Push initiated successfully'}</p>
                            ${checkoutRequestId ? `<p><strong>Checkout Request ID:</strong> ${checkoutRequestId}</p>` : ''}
                        </div>
                        <div class="result-info" style="margin-top: 1rem;">
                            <p><strong>Next Steps:</strong></p>
                            <ol>
                                <li>Check the test phone number for an M-Pesa prompt</li>
                                <li>Enter PIN: <strong>1234</strong></li>
                                <li>Confirm the payment</li>
                                <li>Use the "Query Payment Status" button below to check the transaction status</li>
                            </ol>
                        </div>
                        ${result.data ? `
                            <div class="result-data">
                                <strong>Response Data:</strong>
                                <pre>${JSON.stringify(result.data, null, 2)}</pre>
                            </div>
                        ` : ''}
                    `;
                    
                    if (checkoutRequestId) {
                        statusQuerySection.style.display = 'block';
                    }
                } else {
                    resultContent.innerHTML = `
                        <div class="result-error">
                            <strong>✗ Error</strong>
                            <p>${result.message || 'Failed to initiate STK Push'}</p>
                        </div>
                    `;
                }
                
                resultCard.style.display = 'block';
            } catch (error) {
                resultContent.innerHTML = `
                    <div class="result-error">
                        <strong>✗ Error</strong>
                        <p>Network error: ${error.message}</p>
                    </div>
                `;
                resultCard.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.querySelector('.btn-text').style.display = 'inline';
                submitBtn.querySelector('.btn-loading').style.display = 'none';
            }
        });
    }

    if (queryStatusBtn) {
        queryStatusBtn.addEventListener('click', async function() {
            if (!checkoutRequestId) {
                alert('No checkout request ID available');
                return;
            }
            
            queryStatusBtn.disabled = true;
            queryStatusBtn.textContent = 'Querying...';
            
            try {
                const response = await fetch('<?= base_url('staff/dashboard/payment-gateway/mpesa-test/query-status'); ?>?checkout_request_id=' + encodeURIComponent(checkoutRequestId));
                const result = await response.json();
                
                const statusHtml = `
                    <div class="result-${result.success ? 'success' : 'info'}" style="margin-top: 1rem;">
                        <strong>Status Query Result:</strong>
                        <p><strong>Result Code:</strong> ${result.data?.result_code || 'N/A'}</p>
                        <p><strong>Result Description:</strong> ${result.data?.result_desc || result.message || 'N/A'}</p>
                        ${result.data ? `
                            <div class="result-data" style="margin-top: 1rem;">
                                <pre>${JSON.stringify(result.data, null, 2)}</pre>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                resultContent.insertAdjacentHTML('beforeend', statusHtml);
            } catch (error) {
                alert('Error querying status: ' + error.message);
            } finally {
                queryStatusBtn.disabled = false;
                queryStatusBtn.textContent = 'Query Payment Status';
            }
        });
    }

    // Copy callback URL to clipboard
    window.copyCallbackUrl = function() {
        const callbackUrl = document.getElementById('callbackUrl').textContent;
        navigator.clipboard.writeText(callbackUrl).then(function() {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✓ Copied!';
            btn.style.background = '#28a745';
            setTimeout(function() {
                btn.textContent = originalText;
                btn.style.background = '#007bff';
            }, 2000);
        }).catch(function(err) {
            alert('Failed to copy: ' + err);
        });
    };
});
</script>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>

