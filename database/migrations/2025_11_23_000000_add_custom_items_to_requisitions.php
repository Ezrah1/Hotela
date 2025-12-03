<?php

return [
    "ALTER TABLE requisition_items 
     MODIFY COLUMN inventory_item_id INT NULL,
     ADD COLUMN custom_item_name VARCHAR(255) NULL AFTER inventory_item_id",
];

