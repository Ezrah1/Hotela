<?php

return [
    // Enhance pos_item_components with unit conversion support
    "ALTER TABLE pos_item_components 
    ADD COLUMN IF NOT EXISTS source_unit VARCHAR(50) NULL AFTER quantity_per_sale,
    ADD COLUMN IF NOT EXISTS target_unit VARCHAR(50) NULL AFTER source_unit,
    ADD COLUMN IF NOT EXISTS conversion_factor DECIMAL(12,6) DEFAULT 1.0 AFTER target_unit;",
    
    // Enhance inventory_movements with additional tracking fields (if not already present)
    "ALTER TABLE inventory_movements 
    ADD COLUMN IF NOT EXISTS old_quantity DECIMAL(12,3) NULL AFTER quantity,
    ADD COLUMN IF NOT EXISTS new_quantity DECIMAL(12,3) NULL AFTER old_quantity,
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER new_quantity,
    ADD COLUMN IF NOT EXISTS role_key VARCHAR(50) NULL AFTER user_id;",
    
    // Add index for faster movement queries
    "CREATE INDEX IF NOT EXISTS idx_movements_item_location ON inventory_movements(item_id, location_id);",
    "CREATE INDEX IF NOT EXISTS idx_movements_type ON inventory_movements(type);",
    "CREATE INDEX IF NOT EXISTS idx_movements_created ON inventory_movements(created_at);",
    
    // Add director_approved field to requisitions for final approval
    "ALTER TABLE requisitions 
    ADD COLUMN IF NOT EXISTS director_approved TINYINT(1) DEFAULT 0 AFTER finance_approved,
    ADD COLUMN IF NOT EXISTS director_approved_by INT NULL AFTER director_approved,
    ADD COLUMN IF NOT EXISTS director_approved_at TIMESTAMP NULL AFTER director_approved_by,
    ADD COLUMN IF NOT EXISTS director_notes TEXT NULL AFTER director_approved_at;",
];

