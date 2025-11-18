<?php

return [
    "CREATE TABLE IF NOT EXISTS requisitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) NOT NULL UNIQUE,
        requested_by INT NULL,
        status ENUM('pending','approved','rejected','ordered','received') DEFAULT 'pending',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_requisitions_user FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS requisition_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requisition_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_requisition_items_req FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE,
        CONSTRAINT fk_requisition_items_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requisition_id INT NULL,
        supplier_name VARCHAR(150) NOT NULL,
        status ENUM('draft','sent','received','cancelled') DEFAULT 'draft',
        expected_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_po_requisition FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        unit_cost DECIMAL(12,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_po_items_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_po_items_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS goods_receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT NOT NULL,
        received_by INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_goods_receipts_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_goods_receipts_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS goods_receipt_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        goods_receipt_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_receipt_items_receipt FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
        CONSTRAINT fk_receipt_items_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

