<?php

return [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE settings ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE room_types ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE pos_categories ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE pos_items ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE pos_tills ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE inventory_locations ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE inventory_levels ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;",
    "ALTER TABLE pos_item_components ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER id;"
];


