<?php

return [
    "CREATE TABLE IF NOT EXISTS expense_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) NULL,
        department VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expense_categories_tenant (tenant_id),
        INDEX idx_expense_categories_department (department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        reference VARCHAR(50) NOT NULL,
        category_id INT NULL,
        supplier_id INT NULL,
        department VARCHAR(50) NULL,
        description TEXT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        expense_date DATE NOT NULL,
        payment_method ENUM('cash','bank_transfer','cheque','mpesa','card','other') DEFAULT 'bank_transfer',
        bill_reference VARCHAR(100) NULL,
        is_recurring TINYINT(1) DEFAULT 0,
        recurring_frequency ENUM('daily','weekly','monthly','quarterly','yearly') NULL,
        status ENUM('pending','approved','paid','cancelled') DEFAULT 'pending',
        approved_by INT NULL,
        paid_by INT NULL,
        paid_at TIMESTAMP NULL,
        notes TEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY expenses_tenant_reference_unique (tenant_id, reference),
        CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
        CONSTRAINT fk_expenses_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        CONSTRAINT fk_expenses_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_expenses_paid_by FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_expenses_tenant (tenant_id),
        INDEX idx_expenses_date (expense_date),
        INDEX idx_expenses_department (department),
        INDEX idx_expenses_status (status),
        INDEX idx_expenses_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS expense_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(50) NULL,
        file_size INT NULL,
        uploaded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_expense_attachments_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
        CONSTRAINT fk_expense_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

