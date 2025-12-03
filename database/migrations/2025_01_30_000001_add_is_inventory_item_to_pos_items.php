<?php

return [
    // Add is_inventory_item flag to pos_items table
    // This distinguishes items that should appear in inventory (like raw materials) 
    // from non-inventory POS items (like tea, plain coffee, hot water)
    "ALTER TABLE pos_items ADD COLUMN IF NOT EXISTS is_inventory_item TINYINT(1) DEFAULT 0 AFTER tracked;",
    
    // Add production_cost column to track cost of ingredients used
    "ALTER TABLE pos_items ADD COLUMN IF NOT EXISTS production_cost DECIMAL(10,2) DEFAULT 0.00 AFTER price;",
    
    // Add index for faster filtering
    "CREATE INDEX IF NOT EXISTS idx_pos_items_is_inventory ON pos_items(is_inventory_item);",
];

