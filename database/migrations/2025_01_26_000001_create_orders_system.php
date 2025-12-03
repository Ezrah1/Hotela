<?php

return [
    // Create orders table (enhanced from pos_sales for full order management)
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) NOT NULL UNIQUE,
        order_type ENUM('room_service', 'restaurant', 'takeaway', 'website_delivery', 'pos_order', 'supplier') DEFAULT 'pos_order',
        source ENUM('website', 'pos', 'front_desk', 'room_service', 'supplier') DEFAULT 'pos',
        user_id INT NULL,
        reservation_id INT NULL,
        customer_name VARCHAR(150) NULL,
        customer_phone VARCHAR(50) NULL,
        customer_email VARCHAR(150) NULL,
        room_number VARCHAR(20) NULL,
        service_type ENUM('dine_in', 'takeaway', 'delivery', 'room_service', 'pickup') NULL,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_type VARCHAR(20) DEFAULT 'cash',
        total DECIMAL(12,2) NOT NULL DEFAULT 0,
        notes TEXT NULL,
        special_instructions TEXT NULL,
        assigned_staff_id INT NULL,
        preparation_started_at TIMESTAMP NULL,
        ready_at TIMESTAMP NULL,
        delivered_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL,
        cancellation_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_order_type (order_type),
        INDEX idx_reservation (reservation_id),
        INDEX idx_user (user_id),
        INDEX idx_created_at (created_at),
        CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_orders_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
        CONSTRAINT fk_orders_staff FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Create order_items table
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        pos_item_id INT NULL,
        inventory_item_id INT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,3) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        line_total DECIMAL(12,2) NOT NULL,
        special_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_items_pos_item FOREIGN KEY (pos_item_id) REFERENCES pos_items(id) ON DELETE SET NULL,
        CONSTRAINT fk_order_items_inventory FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Create order_status_logs table for tracking status changes
    "CREATE TABLE IF NOT EXISTS order_status_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        changed_by INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_order_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_logs_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_order_id (order_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Create order_comments table for internal notes
    "CREATE TABLE IF NOT EXISTS order_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NULL,
        comment TEXT NOT NULL,
        visibility ENUM('all', 'kitchen', 'service', 'ops', 'finance') DEFAULT 'all',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_order_comments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_order_id (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Migrate existing pos_sales to orders (if pos_sales table exists)
    "INSERT INTO orders (reference, order_type, source, user_id, reservation_id, customer_name, customer_phone, service_type, status, payment_status, payment_type, total, notes, created_at, updated_at)
    SELECT 
        ps.reference,
        CASE 
            WHEN ps.notes LIKE '%Guest order%' THEN 'website_delivery'
            WHEN ps.reservation_id IS NOT NULL THEN 'room_service'
            ELSE 'pos_order'
        END as order_type,
        CASE 
            WHEN ps.user_id IS NULL THEN 'website'
            ELSE 'pos'
        END as source,
        ps.user_id,
        ps.reservation_id,
        r.guest_name as customer_name,
        r.guest_phone as customer_phone,
        CASE 
            WHEN ps.notes LIKE '%pickup%' THEN 'pickup'
            WHEN ps.notes LIKE '%delivery%' THEN 'delivery'
            ELSE 'dine_in'
        END as service_type,
        CASE 
            WHEN ps.payment_status = 'paid' THEN 'completed'
            WHEN ps.payment_status = 'pending' THEN 'pending'
            ELSE 'pending'
        END as status,
        ps.payment_status,
        ps.payment_type,
        ps.total,
        ps.notes,
        ps.created_at,
        ps.updated_at
    FROM pos_sales ps
    LEFT JOIN reservations r ON r.id = ps.reservation_id
    WHERE NOT EXISTS (SELECT 1 FROM orders o WHERE o.reference = ps.reference)
    LIMIT 0"
];

