<?php

return [
    "CREATE TABLE IF NOT EXISTS petty_cash_account (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        account_name VARCHAR(100) NOT NULL DEFAULT 'Petty Cash',
        balance DECIMAL(12,2) DEFAULT 0,
        limit_amount DECIMAL(12,2) DEFAULT 2000,
        custodian_id INT NULL,
        status ENUM('active','suspended','closed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_petty_cash_custodian FOREIGN KEY (custodian_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_petty_cash_tenant (tenant_id),
        UNIQUE KEY petty_cash_tenant_unique (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS petty_cash_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        account_id INT NOT NULL,
        transaction_type ENUM('deposit','withdrawal','expense') DEFAULT 'withdrawal',
        amount DECIMAL(12,2) NOT NULL,
        description TEXT NOT NULL,
        expense_id INT NULL,
        receipt_number VARCHAR(50) NULL,
        authorized_by INT NULL,
        processed_by INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_petty_cash_trans_account FOREIGN KEY (account_id) REFERENCES petty_cash_account(id) ON DELETE CASCADE,
        CONSTRAINT fk_petty_cash_trans_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE SET NULL,
        CONSTRAINT fk_petty_cash_trans_authorized FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_petty_cash_trans_processed FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_petty_cash_trans_tenant (tenant_id),
        INDEX idx_petty_cash_trans_account (account_id),
        INDEX idx_petty_cash_trans_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

