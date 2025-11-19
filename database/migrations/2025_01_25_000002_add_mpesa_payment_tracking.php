<?php

return [
    // Add M-Pesa tracking columns to pos_sales
    "ALTER TABLE pos_sales 
     ADD COLUMN mpesa_phone VARCHAR(20) NULL AFTER payment_type,
     ADD COLUMN mpesa_checkout_request_id VARCHAR(100) NULL AFTER mpesa_phone,
     ADD COLUMN mpesa_merchant_request_id VARCHAR(100) NULL AFTER mpesa_checkout_request_id,
     ADD COLUMN mpesa_status ENUM('pending', 'completed', 'failed', 'cancelled') NULL AFTER mpesa_merchant_request_id,
     ADD COLUMN mpesa_transaction_id VARCHAR(50) NULL AFTER mpesa_status,
     ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending' AFTER mpesa_transaction_id",
    
    // Add M-Pesa tracking columns to reservations
    "ALTER TABLE reservations 
     ADD COLUMN payment_method VARCHAR(20) NULL AFTER payment_status,
     ADD COLUMN mpesa_phone VARCHAR(20) NULL AFTER payment_method,
     ADD COLUMN mpesa_checkout_request_id VARCHAR(100) NULL AFTER mpesa_phone,
     ADD COLUMN mpesa_merchant_request_id VARCHAR(100) NULL AFTER mpesa_checkout_request_id,
     ADD COLUMN mpesa_status ENUM('pending', 'completed', 'failed', 'cancelled') NULL AFTER mpesa_merchant_request_id,
     ADD COLUMN mpesa_transaction_id VARCHAR(50) NULL AFTER mpesa_status",
    
    // Create payment_transactions table for comprehensive payment tracking
    "CREATE TABLE IF NOT EXISTS payment_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_type ENUM('pos_sale', 'booking', 'other') NOT NULL,
        reference_id INT NOT NULL,
        reference_code VARCHAR(50) NULL,
        payment_method VARCHAR(20) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        phone_number VARCHAR(20) NULL,
        checkout_request_id VARCHAR(100) NULL,
        merchant_request_id VARCHAR(100) NULL,
        mpesa_transaction_id VARCHAR(50) NULL,
        status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        callback_data TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_reference (transaction_type, reference_id),
        INDEX idx_checkout_request (checkout_request_id),
        INDEX idx_merchant_request (merchant_request_id),
        INDEX idx_mpesa_transaction (mpesa_transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

