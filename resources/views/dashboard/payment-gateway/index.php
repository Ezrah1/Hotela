<?php
$gateway = $_GET['gateway'] ?? 'mpesa';
$gatewaySettings = $gatewaySettings ?? [];
ob_start();
?>

<div class="page-header">
    <h1>Payment Gateways</h1>
    <p>Configure payment methods for your platform</p>
</div>

<?php
$isInSettings = false;
include view_path('dashboard/payment-gateway/gateway-form.php');
?>

<style>
.payment-gateway-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.gateway-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    overflow-x: auto;
    scrollbar-width: thin;
}

.gateway-tabs::-webkit-scrollbar {
    height: 6px;
}

.gateway-tabs::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.gateway-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #6c757d;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.gateway-tab:hover {
    background: #e9ecef;
    color: #495057;
}

.gateway-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: white;
    font-weight: 500;
}

.gateway-icon {
    font-size: 1.2rem;
}

.gateway-content {
    padding: 2rem;
}

.gateway-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.gateway-info h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: #212529;
}

.gateway-description {
    color: #6c757d;
    margin: 0;
    font-size: 0.95rem;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"] {
    position: relative;
    width: 48px;
    height: 24px;
    appearance: none;
    background: #ccc;
    border-radius: 12px;
    transition: background 0.3s;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"]:checked {
    background: #28a745;
}

.toggle-switch input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: left 0.3s;
}

.toggle-switch input[type="checkbox"]:checked::before {
    left: 26px;
}

.toggle-label {
    font-weight: 500;
    color: #495057;
}

.gateway-fields {
    margin-bottom: 2rem;
}

.gateway-fields fieldset {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 0;
}

.gateway-fields legend {
    font-weight: 600;
    color: #495057;
    padding: 0 0.75rem;
    font-size: 1.1rem;
}

.gateway-fields label {
    display: block;
    margin-bottom: 1.25rem;
}

.gateway-fields label:last-child {
    margin-bottom: 0;
}

.gateway-fields label span {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.gateway-fields input[type="text"],
.gateway-fields input[type="password"],
.gateway-fields input[type="number"],
.gateway-fields select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.gateway-fields input:focus,
.gateway-fields select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.gateway-actions {
    display: flex;
    gap: 1rem;
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

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .gateway-header {
        flex-direction: column;
        gap: 1rem;
    }

    .gateway-tabs {
        flex-wrap: nowrap;
    }

    .gateway-tab {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }

    .gateway-content {
        padding: 1.5rem;
    }
}
</style>

<?php
$slot = ob_get_clean();
include view_path('layouts/dashboard.php');
?>
