<?php

return [
    "CREATE TABLE IF NOT EXISTS system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'string',
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Insert default settings
    "INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
        ('hotel_name', 'Joyce Resorts', 'string', 'Hotel business name'),
        ('hotel_logo', '', 'string', 'Hotel logo URL or path'),
        ('contact_email', '', 'string', 'Primary contact email'),
        ('contact_phone', '', 'string', 'Primary contact phone'),
        ('contact_address', '', 'string', 'Business address'),
        ('currency', 'KES', 'string', 'Default currency code'),
        ('currency_symbol', 'KSh', 'string', 'Currency symbol'),
        ('tax_rate', '0', 'decimal', 'Default tax rate percentage'),
        ('timezone', 'Africa/Nairobi', 'string', 'System timezone'),
        ('date_format', 'Y-m-d', 'string', 'Date display format'),
        ('time_format', 'H:i', 'string', 'Time display format'),
        ('inventory_low_stock_threshold', '10', 'integer', 'Low stock alert threshold'),
        ('pos_receipt_footer', 'Thank you for your business!', 'string', 'POS receipt footer text'),
        ('website_enabled', '1', 'boolean', 'Enable public website'),
        ('booking_enabled', '1', 'boolean', 'Enable online bookings')
    ON DUPLICATE KEY UPDATE setting_key = setting_key;",
];

