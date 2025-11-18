<?php

return [
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS avg_cost DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER reorder_point;"
];

