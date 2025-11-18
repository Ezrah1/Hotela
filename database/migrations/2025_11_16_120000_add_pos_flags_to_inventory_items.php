<?php

return [
    // Add POS-related flags to inventory_items (idempotent)
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS is_pos_item TINYINT(1) DEFAULT 0 AFTER avg_cost;",
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active' AFTER is_pos_item;",
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS allow_negative TINYINT(1) DEFAULT 0 AFTER status;",
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER allow_negative;",
];


