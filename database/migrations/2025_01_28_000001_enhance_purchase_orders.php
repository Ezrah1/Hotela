<?php

return [
    // Enhance purchase_orders table with delivery tracking, invoice, and payment fields
    "ALTER TABLE purchase_orders 
        ADD COLUMN IF NOT EXISTS delivery_date DATE NULL AFTER expected_date,
        ADD COLUMN IF NOT EXISTS received_date DATE NULL AFTER delivery_date,
        ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL AFTER received_date,
        ADD COLUMN IF NOT EXISTS invoice_path VARCHAR(500) NULL AFTER invoice_number,
        ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid' AFTER invoice_path,
        ADD COLUMN IF NOT EXISTS payment_date DATE NULL AFTER payment_status,
        ADD COLUMN IF NOT EXISTS total_amount DECIMAL(12,2) DEFAULT 0.00 AFTER payment_date,
        ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(12,2) DEFAULT 0.00 AFTER total_amount,
        ADD INDEX idx_po_payment_status (payment_status),
        ADD INDEX idx_po_delivery_date (delivery_date),
        ADD INDEX idx_po_expected_date (expected_date);",

    // Update status enum to include more states
    "ALTER TABLE purchase_orders 
        MODIFY COLUMN status ENUM('draft','sent','in_transit','received','partial','cancelled') DEFAULT 'draft';",
];

