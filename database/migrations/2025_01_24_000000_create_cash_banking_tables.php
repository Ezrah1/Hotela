<?php

return [
"CREATE TABLE IF NOT EXISTS pos_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift_date DATE NOT NULL,
    cash_declared DECIMAL(12,2) DEFAULT 0,
    cash_calculated DECIMAL(12,2) DEFAULT 0,
    difference DECIMAL(12,2) DEFAULT 0,
    status ENUM('open','closed','reconciled') DEFAULT 'open',
    closed_at TIMESTAMP NULL,
    closed_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY pos_shifts_user_date_unique (user_id, shift_date),
    CONSTRAINT fk_pos_shifts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_shifts_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS cash_banking_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_reference VARCHAR(50) NOT NULL UNIQUE,
    shift_date DATE NOT NULL,
    total_cash DECIMAL(12,2) NOT NULL,
    status ENUM('unbanked','ready_for_banking','banked') DEFAULT 'unbanked',
    scheduled_banking_date DATE NULL,
    banked_date DATE NULL,
    banked_by INT NULL,
    deposit_slip_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_banking_batches_banked_by FOREIGN KEY (banked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS cash_banking_batch_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    shift_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_batch_shifts_batch FOREIGN KEY (batch_id) REFERENCES cash_banking_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_batch_shifts_shift FOREIGN KEY (shift_id) REFERENCES pos_shifts(id) ON DELETE CASCADE,
    UNIQUE KEY batch_shifts_unique (batch_id, shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS cash_reconciliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    reconciled_by INT NOT NULL,
    cash_declared DECIMAL(12,2) NOT NULL,
    cash_calculated DECIMAL(12,2) NOT NULL,
    difference DECIMAL(12,2) DEFAULT 0,
    adjustment_amount DECIMAL(12,2) DEFAULT 0,
    adjustment_reason TEXT NULL,
    notes TEXT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_reconciliations_batch FOREIGN KEY (batch_id) REFERENCES cash_banking_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_cash_reconciliations_user FOREIGN KEY (reconciled_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

"CREATE TABLE IF NOT EXISTS banking_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    is_banking_day TINYINT(1) DEFAULT 1,
    reason VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_banking_days_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

