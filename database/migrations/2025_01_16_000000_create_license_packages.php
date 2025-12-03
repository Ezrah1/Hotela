<?php

return [
    "CREATE TABLE IF NOT EXISTS license_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        currency VARCHAR(3) DEFAULT 'USD',
        duration_months INT NOT NULL DEFAULT 12 COMMENT 'License duration in months',
        features JSON NULL COMMENT 'Package features',
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS license_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        package_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'USD',
        payment_method VARCHAR(50) NOT NULL COMMENT 'mpesa, card, bank_transfer, etc.',
        payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        payment_reference VARCHAR(255) NULL COMMENT 'External payment reference',
        transaction_id VARCHAR(255) NULL COMMENT 'Gateway transaction ID',
        mpesa_phone VARCHAR(20) NULL,
        mpesa_checkout_request_id VARCHAR(255) NULL,
        mpesa_merchant_request_id VARCHAR(255) NULL,
        mpesa_status VARCHAR(50) NULL,
        metadata JSON NULL COMMENT 'Additional payment metadata',
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_package (package_id),
        INDEX idx_status (payment_status),
        INDEX idx_reference (payment_reference),
        INDEX idx_transaction (transaction_id),
        CONSTRAINT fk_license_payment_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
        CONSTRAINT fk_license_payment_package FOREIGN KEY (package_id) REFERENCES license_packages(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

