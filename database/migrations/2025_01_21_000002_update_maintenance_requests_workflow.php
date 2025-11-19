<?php

return [
    "ALTER TABLE maintenance_requests 
    ADD COLUMN IF NOT EXISTS status ENUM('pending','ops_review','finance_review','approved','assigned','in_progress','completed','verified','cancelled') DEFAULT 'pending' AFTER priority,
    ADD COLUMN IF NOT EXISTS cost_estimate DECIMAL(10,2) NULL AFTER notes,
    ADD COLUMN IF NOT EXISTS supplier_id INT NULL AFTER cost_estimate,
    ADD COLUMN IF NOT EXISTS ops_notes TEXT NULL AFTER supplier_id,
    ADD COLUMN IF NOT EXISTS finance_notes TEXT NULL AFTER ops_notes,
    ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER finance_notes,
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER approved_by,
    ADD COLUMN IF NOT EXISTS work_order_reference VARCHAR(50) NULL AFTER approved_at,
    ADD COLUMN IF NOT EXISTS photos JSON NULL AFTER work_order_reference,
    ADD COLUMN IF NOT EXISTS materials_needed TEXT NULL AFTER photos,
    ADD COLUMN IF NOT EXISTS recommended_suppliers TEXT NULL AFTER materials_needed,
    ADD COLUMN IF NOT EXISTS verified_by INT NULL AFTER completed_at,
    ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL AFTER verified_by,
    ADD CONSTRAINT fk_maintenance_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_maintenance_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_maintenance_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;",
    
    "UPDATE maintenance_requests SET status = 'pending' WHERE status IS NULL OR status = '';"
];

