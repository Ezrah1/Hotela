<?php

return [
"CREATE TABLE IF NOT EXISTS folios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    reservation_id INT NOT NULL,
    total DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) DEFAULT 0,
    status ENUM('open','closed','refunded') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_folios_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS folio_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('charge','payment') DEFAULT 'charge',
    source VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_folio_entries_folio FOREIGN KEY (folio_id) REFERENCES folios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

