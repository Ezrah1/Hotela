<?php

return [
"CREATE TABLE IF NOT EXISTS pos_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY pos_categories_tenant_name_unique (tenant_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
"CREATE TABLE IF NOT EXISTS pos_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sku VARCHAR(50) NULL,
    tracked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_items_category FOREIGN KEY (category_id) REFERENCES pos_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS pos_tills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS pos_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    reference VARCHAR(50) NOT NULL,
    user_id INT NULL,
    till_id INT NULL,
    payment_type ENUM('cash','mpesa','card','room','corporate') DEFAULT 'cash',
    total DECIMAL(12,2) NOT NULL,
    notes VARCHAR(255) NULL,
    reservation_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY pos_sales_tenant_reference_unique (tenant_id, reference),
    CONSTRAINT fk_pos_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pos_sales_till FOREIGN KEY (till_id) REFERENCES pos_tills(id) ON DELETE SET NULL,
    CONSTRAINT fk_pos_sales_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS pos_sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    item_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_sale_items_sale FOREIGN KEY (sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_sale_items_item FOREIGN KEY (item_id) REFERENCES pos_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

