# Role Hierarchy & System Admin Migration Guide

## Overview

This migration restructures the role hierarchy to make **Director** the highest role within each hotel installation, and creates a separate **System Admin** role for software owners that is completely isolated from tenant operations.

## Key Changes

### 1. Role Hierarchy
- **Director** is now the highest tenant role (replaces Admin)
- **Admin** role is deprecated and hidden from tenant UIs
- **System Admin** is a separate system for software owners only

### 2. Authentication Separation
- **Tenant routes** (`/staff/*`) use `TenantAuth` middleware
- **System admin routes** (`/sysadmin/*`) use `SystemAuth` middleware
- System admins cannot access tenant routes and vice versa

### 3. Database Changes
- New `system_admins` table for software owner accounts
- New `license_activations` table for license management
- New `system_audit_logs` table for system admin actions
- `roles` table updated with `is_tenant_role` flag

## Migration Steps

### Step 1: Run Database Migrations

```bash
php scripts/run_ignored_anomalies_migration.php
php scripts/run_role_migration.php
```

Or manually run:
```bash
php scripts/create_system_admin.php
```

### Step 2: Verify Migration

1. Check that existing admin users were migrated to director:
   ```sql
   SELECT id, name, email, role_key FROM users WHERE role_key = 'director';
   ```

2. Verify system admin was created:
   ```sql
   SELECT * FROM system_admins;
   ```

3. Check roles table:
   ```sql
   SELECT `key`, name, is_tenant_role FROM roles;
   ```

### Step 3: Test Access

1. **Test Director Login:**
   - Login as a director user
   - Verify access to `/staff/dashboard`
   - Verify director is shown as highest role in staff management

2. **Test System Admin:**
   - Navigate to `/sysadmin/login`
   - Login with system admin credentials
   - Verify access to `/sysadmin/dashboard`
   - Verify cannot access `/staff/dashboard`

3. **Test Tenant Route Protection:**
   - Try accessing `/staff/dashboard` as system admin (should fail)
   - Try accessing `/sysadmin/dashboard` as tenant user (should fail)

## Default Credentials

### System Admin
- **Username:** `sysadmin` (or from `SYSTEM_ADMIN_USERNAME` env var)
- **Email:** `sysadmin@hotela.local` (or from `SYSTEM_ADMIN_EMAIL` env var)
- **Password:** `ChangeMe123!` (or from `SYSTEM_ADMIN_PASSWORD` env var)

**⚠️ IMPORTANT:** Change the default password immediately after first login!

## Environment Variables

Add to `.env` file:
```env
SYSTEM_ADMIN_USERNAME=sysadmin
SYSTEM_ADMIN_EMAIL=sysadmin@hotela.local
SYSTEM_ADMIN_PASSWORD=YourSecurePassword123!
```

## License Activation

Directors will be prompted to activate their license on first access. The license activation:
- Binds to installation ID
- Binds to director email
- Stores signed token for integrity verification

## Security Features

1. **Route Separation:** Tenant and system admin routes are completely isolated
2. **Middleware Protection:** Automatic middleware application based on route patterns
3. **Audit Logging:** All system admin actions are logged
4. **License Binding:** Licenses are bound to installation and director account

## UI Changes

- Admin role removed from all tenant UI dropdowns
- Director shown as highest assignable role
- System admin panel accessible only via `/sysadmin/login`
- No references to "admin" in tenant-facing help text

## Rollback

If you need to rollback:

1. Restore admin role visibility:
   ```sql
   UPDATE roles SET is_tenant_role = 1 WHERE `key` = 'admin';
   ```

2. Revert user roles (if needed):
   ```sql
   UPDATE users SET role_key = 'admin' WHERE role_key = 'director' AND [your conditions];
   ```

3. Remove system admin tables (if needed):
   ```sql
   DROP TABLE IF EXISTS system_audit_logs;
   DROP TABLE IF EXISTS license_activations;
   DROP TABLE IF EXISTS system_admins;
   ALTER TABLE roles DROP COLUMN IF EXISTS is_tenant_role;
   ```

## Next Steps

1. Update any custom code that references 'admin' role
2. Update documentation to reflect director as highest role
3. Train staff on new role hierarchy
4. Set up license activation for production
5. Configure system admin credentials securely

## Support

For issues or questions, contact the development team.

