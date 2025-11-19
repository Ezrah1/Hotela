<?php

return [
    "ALTER TABLE inventory_movements 
     ADD COLUMN old_quantity DECIMAL(12,3) NULL AFTER notes,
     ADD COLUMN new_quantity DECIMAL(12,3) NULL AFTER old_quantity,
     ADD COLUMN user_id INT NULL AFTER new_quantity,
     ADD COLUMN role_key VARCHAR(50) NULL AFTER user_id,
     ADD CONSTRAINT fk_inventory_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL"
];

