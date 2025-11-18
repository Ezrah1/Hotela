<?php

return [
    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        reference VARCHAR(50) NOT NULL,
        payment_type ENUM('expense','bill','supplier','other') NOT NULL,
        expense_id INT NULL,
        bill_id INT NULL,
        supplier_id INT NULL,
        amount DECIMAL(12,2) NOT NULL,
        payment_method ENUM('cash','bank_transfer','cheque','mpesa','card','other') DEFAULT 'bank_transfer',
        payment_date DATE NOT NULL,
        transaction_reference VARCHAR(100) NULL,
        cheque_number VARCHAR(50) NULL,
        bank_name VARCHAR(100) NULL,
        account_number VARCHAR(50) NULL,
        notes TEXT NULL,
        status ENUM('pending','completed','failed','cancelled') DEFAULT 'completed',
        processed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY payments_tenant_reference_unique (tenant_id, reference),
        CONSTRAINT fk_payments_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE SET NULL,
        CONSTRAINT fk_payments_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        CONSTRAINT fk_payments_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_payments_tenant (tenant_id),
        INDEX idx_payments_type (payment_type),
        INDEX idx_payments_date (payment_date),
        INDEX idx_payments_status (status),
        INDEX idx_payments_expense (expense_id),
        INDEX idx_payments_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

