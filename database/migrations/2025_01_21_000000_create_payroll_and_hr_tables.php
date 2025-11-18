<?php

return [
    "CREATE TABLE IF NOT EXISTS payroll (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        user_id INT NOT NULL,
        pay_period_start DATE NOT NULL,
        pay_period_end DATE NOT NULL,
        basic_salary DECIMAL(12,2) DEFAULT 0,
        allowances DECIMAL(12,2) DEFAULT 0,
        deductions DECIMAL(12,2) DEFAULT 0,
        net_salary DECIMAL(12,2) DEFAULT 0,
        status ENUM('pending','processed','paid','cancelled') DEFAULT 'pending',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_payroll_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_payroll_tenant (tenant_id),
        INDEX idx_payroll_user (user_id),
        INDEX idx_payroll_period (pay_period_start, pay_period_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS employee_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        user_id INT NOT NULL,
        type ENUM('performance','disciplinary','training','award','note','other') DEFAULT 'note',
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_employee_records_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_employee_records_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_employee_records_tenant (tenant_id),
        INDEX idx_employee_records_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

