<?php

return [
    // Add reference field to purchase_orders table
    "ALTER TABLE purchase_orders 
        ADD COLUMN IF NOT EXISTS reference VARCHAR(50) NULL AFTER id,
        ADD UNIQUE INDEX idx_po_reference (reference) IF NOT EXISTS;",
];

