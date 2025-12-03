# Supplier Portal Implementation

## Overview
A comprehensive supplier portal system that allows suppliers to log in using password or email codes (similar to guest accounts) to access their purchase orders, track deliveries, view invoices, and monitor payment status.

## Features Implemented

### 1. Supplier Authentication ✅
- **Password Login**: Suppliers can set passwords and log in with email + password
- **Email Code Login**: Suppliers can request a 6-digit code sent to their email (expires in 15 minutes)
- **Portal Access Control**: `portal_enabled` flag controls which suppliers can access the portal
- **Session Management**: Secure session-based authentication using `SupplierPortal` support class

### 2. Supplier Portal Dashboard ✅
- **Statistics Overview**:
  - Total Orders
  - Pending Orders
  - Completed Orders
  - Unpaid Invoices
  - Total Amount
- **Recent Purchase Orders**: List of latest purchase orders with status and payment information
- **Performance History**: Recent performance ratings and delivery metrics

### 3. Purchase Orders Management ✅
- **View All Orders**: Filterable list of all purchase orders
- **Order Details**: Detailed view of each purchase order including:
  - Order items with quantities and prices
  - Order status (draft, sent, in_transit, received, partial, cancelled)
  - Payment status (unpaid, partial, paid, overdue)
  - Expected delivery dates
  - Total amounts

### 4. Database Structure ✅

#### New Tables
- **supplier_login_codes**: Stores temporary login codes for email-based authentication
  - Links to supplier_id
  - Expires after 15 minutes
  - One-time use codes

#### Enhanced Tables
- **suppliers**: Added fields
  - `password_hash`: Hashed password for password-based login
  - `portal_enabled`: Flag to enable/disable portal access (default: 1)

- **purchase_orders**: Enhanced with
  - `reference`: Unique PO reference number
  - `delivery_date`: Actual delivery date
  - `received_date`: Date when order was received
  - `invoice_number`: Invoice reference
  - `invoice_path`: Path to uploaded invoice file
  - `payment_status`: Payment tracking (unpaid, partial, paid, overdue)
  - `payment_date`: Date of payment
  - `total_amount`: Total order amount
  - `paid_amount`: Amount paid so far

## Routes

### Public Routes (No Authentication Required)
- `GET /supplier/login` - Show login page
- `POST /supplier/login` - Authenticate (password or code)
- `POST /supplier/login/request-code` - Request email code (AJAX)

### Protected Routes (Requires Supplier Login)
- `GET /supplier/portal` - Dashboard
- `GET /supplier/purchase-orders` - List all purchase orders
- `GET /supplier/purchase-order` - View purchase order details
- `POST /supplier/logout` - Sign out

## Usage

### For Suppliers

1. **First Time Setup**:
   - Admin sets up supplier account with email
   - Admin can set password or supplier uses email code login
   - Portal access is enabled by default

2. **Login Options**:
   - **Password Login**: Enter email and password
   - **Email Code Login**: 
     - Enter email
     - Click "Request Code"
     - Enter 6-digit code received via email
     - Code expires in 15 minutes

3. **Portal Features**:
   - View all purchase orders assigned to them
   - Track order status and delivery dates
   - Monitor payment status
   - View performance history
   - See order details and line items

### For Administrators

1. **Enable Portal Access**:
   - Go to Suppliers → Edit Supplier
   - Ensure `portal_enabled` is set to 1 (default)
   - Set password if needed (optional - suppliers can use email codes)

2. **Set Supplier Password** (Optional):
   - Can be set during supplier creation/edit
   - Password is hashed using PHP's `password_hash()`
   - If not set, supplier must use email code login

## Security Features

- **Rate Limiting**: Email code requests limited to once per 2 minutes
- **Code Expiration**: Login codes expire after 15 minutes
- **One-Time Use**: Codes are marked as used after successful login
- **Password Hashing**: Passwords stored using secure hashing
- **Session-Based**: Secure session management
- **Portal Control**: Can disable portal access per supplier

## Email Integration

- **Login Code Emails**: Automatically sent when supplier requests code
- **Email Template**: Professional HTML email with code prominently displayed
- **Branding**: Uses hotel branding settings

## Future Enhancements (Optional)

1. **Invoice Upload**: Allow suppliers to upload invoices directly
2. **Delivery Updates**: Suppliers can update delivery status
3. **Payment Confirmations**: Suppliers can confirm receipt of payments
4. **Performance Feedback**: Suppliers can view and respond to performance ratings
5. **Notifications**: Email/SMS notifications for new orders
6. **Document Management**: Upload delivery notes, receipts, etc.

## Files Created/Modified

### New Files
- `app/Support/SupplierPortal.php` - Portal authentication support
- `app/Repositories/SupplierLoginCodeRepository.php` - Login code management
- `app/Modules/Suppliers/Controllers/SupplierPortalController.php` - Portal controller
- `resources/views/supplier/login.php` - Login page
- `resources/views/supplier/dashboard.php` - Dashboard
- `resources/views/supplier/purchase-orders.php` - Orders list
- `resources/views/supplier/purchase-order-detail.php` - Order details
- `database/migrations/2025_01_28_000002_create_supplier_portal.php` - Portal migration
- `database/migrations/2025_01_28_000003_add_reference_to_purchase_orders.php` - PO reference migration

### Modified Files
- `app/Repositories/SupplierRepository.php` - Added `findByEmail()` method
- `app/Services/Email/EmailService.php` - Added `sendSupplierLoginCode()` method
- `routes/web.php` - Added supplier portal routes

## Access URL

Suppliers can access the portal at:
**`https://hotela.ezrahkiilu.com/supplier/login`**

After login, they'll be redirected to:
**`https://hotela.ezrahkiilu.com/supplier/portal`**

## Next Steps

1. Run migration: `php scripts/run_supplier_portal_migration.php`
2. Set passwords for existing suppliers (optional)
3. Test login with both password and email code methods
4. Notify suppliers about portal access
5. Train suppliers on using the portal

