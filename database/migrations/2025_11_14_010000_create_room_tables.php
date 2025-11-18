<?php

return [
"CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    domain VARCHAR(150) NOT NULL UNIQUE,
    status ENUM('active','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    max_guests INT DEFAULT 2,
    base_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    amenities JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    room_number VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NULL,
    room_type_id INT NOT NULL,
    floor VARCHAR(20) NULL,
    status ENUM('available','occupied','maintenance','blocked') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY rooms_tenant_room_unique (tenant_id, room_number),
    CONSTRAINT fk_rooms_room_type FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    reference VARCHAR(50) NOT NULL,
    guest_name VARCHAR(150) NOT NULL,
    guest_email VARCHAR(150) NULL,
    guest_phone VARCHAR(50) NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    room_type_id INT NOT NULL,
    room_id INT NULL,
    extras JSON NULL,
    source ENUM('website','walk_in','corporate','staff') DEFAULT 'website',
    status ENUM('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(12,2) DEFAULT 0,
    deposit_amount DECIMAL(12,2) DEFAULT 0,
    payment_status ENUM('unpaid','partial','paid','refunded') DEFAULT 'unpaid',
    check_in_status ENUM('scheduled','checked_in','checked_out') DEFAULT 'scheduled',
    room_status ENUM('pending','in_house','departed') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY reservations_tenant_reference_unique (tenant_id, reference),
    CONSTRAINT fk_reservations_room_type FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS reservation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    actor VARCHAR(150) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservation_logs_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

