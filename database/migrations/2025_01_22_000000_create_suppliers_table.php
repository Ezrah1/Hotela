<?php

return [
    "CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        name VARCHAR(150) NOT NULL,
        contact_person VARCHAR(100) NULL,
        email VARCHAR(100) NULL,
        phone VARCHAR(50) NULL,
        address TEXT NULL,
        city VARCHAR(100) NULL,
        country VARCHAR(100) NULL,
        tax_id VARCHAR(50) NULL,
        payment_terms VARCHAR(255) NULL,
        notes TEXT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_suppliers_tenant (tenant_id),
        INDEX idx_suppliers_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "ALTER TABLE purchase_orders 
        ADD COLUMN supplier_id INT NULL AFTER requisition_id,
        ADD CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        ADD INDEX idx_po_supplier (supplier_id);",
];

