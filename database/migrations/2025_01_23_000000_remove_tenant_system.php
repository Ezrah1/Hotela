<?php

// Helper function to safely drop columns and indexes
return [
    // Create a stored procedure to safely drop columns
    "DROP PROCEDURE IF EXISTS drop_column_if_exists;",
    "CREATE PROCEDURE drop_column_if_exists(IN table_name VARCHAR(64), IN column_name VARCHAR(64))
    BEGIN
        IF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = table_name 
            AND COLUMN_NAME = column_name
        ) THEN
            SET @sql = CONCAT('ALTER TABLE ', table_name, ' DROP COLUMN ', column_name);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END;",
    
    // Create a stored procedure to safely drop indexes
    "DROP PROCEDURE IF EXISTS drop_index_if_exists;",
    "CREATE PROCEDURE drop_index_if_exists(IN table_name VARCHAR(64), IN index_name VARCHAR(64))
    BEGIN
        IF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = table_name 
            AND INDEX_NAME = index_name
        ) THEN
            SET @sql = CONCAT('ALTER TABLE ', table_name, ' DROP INDEX ', index_name);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END;",
    
    // Remove tenant_id columns from all tables
    "CALL drop_column_if_exists('users', 'tenant_id');",
    "CALL drop_column_if_exists('settings', 'tenant_id');",
    "CALL drop_column_if_exists('room_types', 'tenant_id');",
    "CALL drop_column_if_exists('rooms', 'tenant_id');",
    "CALL drop_column_if_exists('reservations', 'tenant_id');",
    "CALL drop_column_if_exists('notifications', 'tenant_id');",
    "CALL drop_column_if_exists('pos_categories', 'tenant_id');",
    "CALL drop_column_if_exists('pos_items', 'tenant_id');",
    "CALL drop_column_if_exists('pos_tills', 'tenant_id');",
    "CALL drop_column_if_exists('pos_sales', 'tenant_id');",
    "CALL drop_column_if_exists('pos_item_components', 'tenant_id');",
    "CALL drop_column_if_exists('inventory_locations', 'tenant_id');",
    "CALL drop_column_if_exists('inventory_items', 'tenant_id');",
    "CALL drop_column_if_exists('inventory_levels', 'tenant_id');",
    "CALL drop_column_if_exists('messages', 'tenant_id');",
    "CALL drop_column_if_exists('announcements', 'tenant_id');",
    "CALL drop_column_if_exists('payments', 'tenant_id');",
    "CALL drop_column_if_exists('petty_cash_account', 'tenant_id');",
    "CALL drop_column_if_exists('petty_cash_transactions', 'tenant_id');",
    "CALL drop_column_if_exists('expense_categories', 'tenant_id');",
    "CALL drop_column_if_exists('expenses', 'tenant_id');",
    "CALL drop_column_if_exists('suppliers', 'tenant_id');",
    "CALL drop_column_if_exists('payroll_history', 'tenant_id');",
    "CALL drop_column_if_exists('employee_records', 'tenant_id');",
    
    // Drop tenant-related indexes
    "CALL drop_index_if_exists('users', 'users_tenant_email_unique');",
    "CALL drop_index_if_exists('settings', 'settings_unique');",
    "CALL drop_index_if_exists('rooms', 'rooms_tenant_room_unique');",
    "CALL drop_index_if_exists('reservations', 'reservations_tenant_reference_unique');",
    "CALL drop_index_if_exists('pos_categories', 'pos_categories_tenant_name_unique');",
    "CALL drop_index_if_exists('pos_sales', 'pos_sales_tenant_reference_unique');",
    "CALL drop_index_if_exists('inventory_items', 'inventory_items_tenant_sku_unique');",
    "CALL drop_index_if_exists('inventory_locations', 'inventory_locations_tenant_name_unique');",
    "CALL drop_index_if_exists('expenses', 'expenses_tenant_reference_unique');",
    "CALL drop_index_if_exists('payments', 'payments_tenant_reference_unique');",
    "CALL drop_index_if_exists('petty_cash_account', 'petty_cash_tenant_unique');",
    
    // Clean up stored procedures
    "DROP PROCEDURE IF EXISTS drop_column_if_exists;",
    "DROP PROCEDURE IF EXISTS drop_index_if_exists;",
    
    // Drop tenants table
    "DROP TABLE IF EXISTS tenants;",
];
