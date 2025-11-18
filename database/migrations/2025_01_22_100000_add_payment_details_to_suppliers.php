<?php

return [
    "ALTER TABLE suppliers 
        ADD COLUMN bank_name VARCHAR(100) NULL AFTER payment_terms,
        ADD COLUMN bank_account_number VARCHAR(50) NULL AFTER bank_name,
        ADD COLUMN bank_branch VARCHAR(100) NULL AFTER bank_account_number,
        ADD COLUMN bank_swift_code VARCHAR(20) NULL AFTER bank_branch,
        ADD COLUMN payment_methods VARCHAR(255) NULL AFTER bank_swift_code,
        ADD COLUMN credit_limit DECIMAL(12,2) DEFAULT 0 AFTER payment_methods,
        ADD COLUMN current_balance DECIMAL(12,2) DEFAULT 0 AFTER credit_limit;",
];

