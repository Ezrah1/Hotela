<?php

return [
    // Note: password_hash and portal_enabled columns are added via script
    // This migration only creates the supplier_login_codes table

    // Create supplier login codes table (similar to guest_login_codes)
    "CREATE TABLE IF NOT EXISTS supplier_login_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        email VARCHAR(100) NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_supplier_login_codes_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
        INDEX idx_supplier_login_codes_email (email),
        INDEX idx_supplier_login_codes_code (code),
        INDEX idx_supplier_login_codes_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

