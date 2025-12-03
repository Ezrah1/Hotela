<?php

return [
    // Add selling_price to inventory_items for items that can be sold
    "ALTER TABLE inventory_items 
    ADD COLUMN IF NOT EXISTS selling_price DECIMAL(12,2) DEFAULT 0 AFTER avg_cost;",
];

