<?php

return [
    // Add categorization and grouping fields to suppliers table
    "ALTER TABLE suppliers 
        ADD COLUMN IF NOT EXISTS category ENUM('product_supplier', 'service_provider', 'both') DEFAULT 'product_supplier' AFTER status,
        ADD COLUMN IF NOT EXISTS supplier_group VARCHAR(100) NULL AFTER category,
        ADD COLUMN IF NOT EXISTS status ENUM('active', 'suspended', 'blacklisted', 'inactive') DEFAULT 'active' AFTER supplier_group,
        ADD COLUMN IF NOT EXISTS reliability_score DECIMAL(5,2) DEFAULT 0.00 AFTER status,
        ADD COLUMN IF NOT EXISTS average_delivery_days INT NULL AFTER reliability_score,
        ADD COLUMN IF NOT EXISTS last_order_date DATE NULL AFTER average_delivery_days,
        ADD INDEX idx_suppliers_category (category),
        ADD INDEX idx_suppliers_group (supplier_group),
        ADD INDEX idx_suppliers_status (status);",

    // Create supplier performance tracking table
    "CREATE TABLE IF NOT EXISTS supplier_performance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        purchase_order_id INT NULL,
        order_date DATE NOT NULL,
        delivery_date DATE NULL,
        expected_delivery_date DATE NULL,
        on_time_delivery TINYINT(1) DEFAULT 0,
        quality_rating DECIMAL(3,2) NULL COMMENT 'Rating 0-5',
        price_rating DECIMAL(3,2) NULL COMMENT 'Rating 0-5',
        service_rating DECIMAL(3,2) NULL COMMENT 'Rating 0-5',
        total_rating DECIMAL(3,2) NULL COMMENT 'Average of quality, price, and service',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_supplier_perf_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
        CONSTRAINT fk_supplier_perf_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
        INDEX idx_supplier_perf_supplier (supplier_id),
        INDEX idx_supplier_perf_date (order_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Create supplier-item associations table (which suppliers provide which items)
    "CREATE TABLE IF NOT EXISTS supplier_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        unit_price DECIMAL(12,2) NULL,
        minimum_order_quantity DECIMAL(12,3) DEFAULT 1.00,
        lead_time_days INT NULL,
        is_preferred TINYINT(1) DEFAULT 0,
        last_ordered_date DATE NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_supplier_items_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
        CONSTRAINT fk_supplier_items_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        UNIQUE KEY unique_supplier_item (supplier_id, inventory_item_id),
        INDEX idx_supplier_items_item (inventory_item_id),
        INDEX idx_supplier_items_preferred (is_preferred)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Create supplier pricing history table
    "CREATE TABLE IF NOT EXISTS supplier_pricing_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        effective_date DATE NOT NULL,
        purchase_order_id INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_supplier_pricing_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
        CONSTRAINT fk_supplier_pricing_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_supplier_pricing_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
        INDEX idx_supplier_pricing_supplier (supplier_id),
        INDEX idx_supplier_pricing_item (inventory_item_id),
        INDEX idx_supplier_pricing_date (effective_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Update existing status values if needed (convert 'inactive' to 'inactive' or 'suspended')
    "UPDATE suppliers SET status = 'inactive' WHERE status = 'inactive';",
];

