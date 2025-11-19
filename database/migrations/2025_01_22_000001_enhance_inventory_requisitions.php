<?php

return [
    // Add fields to inventory_items for automatic requisitions
    "ALTER TABLE inventory_items 
    ADD COLUMN IF NOT EXISTS minimum_stock DECIMAL(12,3) DEFAULT 0 AFTER reorder_point;",
    
    "ALTER TABLE inventory_items 
    ADD COLUMN IF NOT EXISTS preferred_supplier_id INT NULL AFTER minimum_stock;",
    
    "SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'inventory_items' 
        AND CONSTRAINT_NAME = 'fk_inventory_preferred_supplier');",
    
    "SET @sql = IF(@constraint_exists = 0, 
        'ALTER TABLE inventory_items ADD CONSTRAINT fk_inventory_preferred_supplier FOREIGN KEY (preferred_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL', 
        'SELECT 1');",
    
    "PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;",
    
    // Enhance requisitions table with workflow fields
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS type ENUM('auto','staff','internal','procurement') DEFAULT 'staff' AFTER reference;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS urgency ENUM('low','medium','high','urgent') DEFAULT 'medium' AFTER type;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS ops_verified TINYINT(1) DEFAULT 0 AFTER status;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS ops_verified_by INT NULL AFTER ops_verified;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS ops_verified_at TIMESTAMP NULL AFTER ops_verified_by;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS ops_notes TEXT NULL AFTER ops_verified_at;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS finance_approved TINYINT(1) DEFAULT 0 AFTER ops_notes;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS finance_approved_by INT NULL AFTER finance_approved;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS finance_approved_at TIMESTAMP NULL AFTER finance_approved_by;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS finance_notes TEXT NULL AFTER finance_approved_at;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS cost_estimate DECIMAL(12,2) DEFAULT 0 AFTER finance_notes;",
    
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS supplier_id INT NULL AFTER cost_estimate;",
    
    // Update requisition_items to include preferred supplier from item
    "ALTER TABLE requisition_items 
    ADD COLUMN IF NOT EXISTS preferred_supplier_id INT NULL AFTER quantity;",
    
    // Update purchase_orders to use supplier_id instead of supplier_name
    "ALTER TABLE purchase_orders 
    ADD COLUMN IF NOT EXISTS supplier_id INT NULL AFTER requisition_id;",
    
    // Create table to track automatic requisition triggers
    "CREATE TABLE IF NOT EXISTS auto_requisition_triggers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_item_id INT NOT NULL,
        location_id INT NOT NULL,
        current_quantity DECIMAL(12,3) NOT NULL,
        reorder_point DECIMAL(12,3) NOT NULL,
        requisition_id INT NULL,
        triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        CONSTRAINT fk_auto_req_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_auto_req_location FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE,
        CONSTRAINT fk_auto_req_requisition FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

