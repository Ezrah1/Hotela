<?php

return [
    // Step 1: Map existing admin users to director role
    "UPDATE users 
     SET role_key = 'director' 
     WHERE role_key = 'admin' 
     AND tenant_id IS NOT NULL",
    
    // Step 2: Mark admin role as deprecated (we'll keep it for now but not show in tenant UIs)
    "ALTER TABLE roles 
     ADD COLUMN IF NOT EXISTS is_tenant_role TINYINT(1) DEFAULT 1 
     AFTER description",
    
    "UPDATE roles 
     SET is_tenant_role = 0 
     WHERE `key` = 'admin'",
    
    "UPDATE roles 
     SET is_tenant_role = 0 
     WHERE `key` = 'super_admin'",
    
    // Step 3: Ensure director is marked as tenant role
    "UPDATE roles 
     SET is_tenant_role = 1 
     WHERE `key` = 'director'"
];

