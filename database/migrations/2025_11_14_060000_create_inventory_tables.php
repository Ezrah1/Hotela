<?php

return [
"CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    category VARCHAR(100) NULL,
    reorder_point DECIMAL(12,3) DEFAULT 0,
    avg_cost DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY inventory_items_tenant_sku_unique (tenant_id, sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS inventory_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY inventory_locations_tenant_name_unique (tenant_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS inventory_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
    UNIQUE KEY inventory_levels_unique (item_id, location_id),
    CONSTRAINT fk_inventory_levels_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_levels_location FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    item_id INT NOT NULL,
    location_id INT NOT NULL,
    type ENUM('purchase','sale','adjustment','transfer','waste') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    reference VARCHAR(100) NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_movements_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_movements_location FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS pos_item_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pos_item_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity_per_sale DECIMAL(12,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_components_item FOREIGN KEY (pos_item_id) REFERENCES pos_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_components_inventory FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

