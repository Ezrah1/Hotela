# Hotela - Integrated Hospitality Management System

A comprehensive property management system (PMS) for hotels, resorts, and hospitality businesses.

## Recent Updates (January 27, 2025)

### 🎉 Customer Portal (Guest Account)
A complete, minimal, and user-friendly guest portal has been implemented. Guests can now:
- View and manage bookings (upcoming and past)
- Track food and drink orders with real-time status
- Submit reviews and ratings
- Contact support
- View notifications
- Manage profile information

**See [GUEST_PORTAL_GUIDE.md](GUEST_PORTAL_GUIDE.md) for detailed user guide.**

### 🛒 Website Ordering System
Fixed and enhanced the website ordering functionality:
- Orders now properly created in unified order management system
- Complete checkout form with all required guest information
- Orders linked to guest accounts for portal access
- Support for cash and M-Pesa payments

### 👥 HR Access
- Finance Managers now have access to Human Resources module
- Can view employee details and add records

**See [CHANGELOG_2025_01_27.md](CHANGELOG_2025_01_27.md) for complete details of all changes.**

---

## System Overview

Hotela is a comprehensive hospitality management system that includes:

### Core Modules
- **Property Management System (PMS)** - Room management, bookings, check-in/out
- **Point of Sale (POS)** - Restaurant, bar, and retail sales
- **Order Management** - Unified order tracking for all order types
- **Inventory Management** - Stock tracking, requisitions, suppliers
- **Human Resources** - Employee records, payroll, attendance
- **Financial Management** - Payments, expenses, bills, petty cash
- **Reports** - Sales, finance, and operations reports
- **Maintenance** - Work order management and tracking
- **Customer Portal** - Guest self-service portal

### Key Features
- Multi-role access control
- Real-time notifications
- M-Pesa payment integration
- Email notifications
- Attendance tracking
- Cash banking and reconciliation
- Supplier management
- Review and rating system

---

## Documentation

- **[CHANGELOG_2025_01_27.md](CHANGELOG_2025_01_27.md)** - Complete changelog for today's updates
- **[GUEST_PORTAL_GUIDE.md](GUEST_PORTAL_GUIDE.md)** - Guest portal user guide
- **[README_MPESA.md](README_MPESA.md)** - M-Pesa integration guide
- **[README_EMAIL.md](README_EMAIL.md)** - Email system documentation
- **[Cloudflare/README.md](Cloudflare/README.md)** - Cloudflare tunnel setup

---

## Quick Start

### Requirements
- PHP 7.4+
- MySQL/MariaDB
- Apache/Nginx
- XAMPP (for local development)

### Installation
1. Clone the repository
2. Configure database in `.env`
3. Run migrations: `php scripts/migrate.php`
4. Access the system at configured URL

### Access Points
- **Staff Login:** `/staff/login` or `/login`
- **Guest Portal:** `/guest/login`
- **Public Website:** `/` (root)

---

## System Architecture

### Technology Stack
- **Backend:** PHP (Custom MVC framework)
- **Database:** MySQL/MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Payment:** M-Pesa STK Push API

### Directory Structure
```
Hotela/
├── app/
│   ├── Core/           # Framework core
│   ├── Modules/        # Feature modules
│   ├── Repositories/   # Data access layer
│   ├── Services/      # Business logic
│   └── Support/        # Helper classes
├── database/
│   └── migrations/     # Database migrations
├── public/             # Web root
├── resources/
│   └── views/          # View templates
└── routes/
    └── platform.php    # Route definitions
```

---

## User Roles

### Staff Roles
- **Admin** - Full system access
- **Director** - Management access
- **Finance Manager** - Financial operations
- **Operations Manager** - Operations oversight
- **Receptionist** - Front desk operations
- **Cashier** - Payment processing
- **Kitchen** - Food preparation
- **Service Agent** - Customer service
- **Housekeeping** - Room maintenance
- **Security** - Security operations
- **Ground/Maintenance** - Facility maintenance

### Guest Access
- **Guest Portal** - Self-service for bookings and orders

---

## Features by Module

### Property Management
- Room type management
- Room status tracking
- Booking management
- Check-in/check-out
- Guest folio
- Room service orders

### Point of Sale
- Menu management
- Order processing
- Payment processing (Cash, M-Pesa)
- Inventory deduction
- Sales reporting

### Order Management
- Unified order tracking
- Status management
- Staff assignment
- Order comments
- Real-time updates

### Inventory
- Stock management
- Automatic requisitions
- Supplier management
- Purchase orders
- Inventory movements

### Human Resources
- Employee records
- Payroll management
- Attendance tracking
- Payslip generation
- Performance records

### Financial
- Payment processing
- Expense tracking
- Bill management
- Petty cash
- Cash banking
- Financial reports

### Maintenance
- Work order creation
- Multi-stage workflow
- Supplier assignment
- Cost tracking
- Work verification

### Customer Portal
- Booking management
- Order tracking
- Review submission
- Contact support
- Notifications

---

## Development

### Running Migrations
```bash
php scripts/migrate.php
```

### Database Backup

**Quick Backup:**
```bash
php scripts/backup_database.php
```

**Full Backup (Database + Files):**
```bash
php scripts/backup.php
```

**Options:**
- `--database-only` - Backup database only
- `--files-only` - Backup files only
- `--output-dir=/path` - Custom backup location

**Default Backup Location:**
- Windows: `C:\Users\USERNAME\Desktop\Backups`
- Linux/Mac: `~/Backups`

See `docs/BACKUP_SYSTEM.md` for detailed documentation.

### Git Repository
- **Repository:** https://github.com/Ezrah1/Hotela
- **Branch:** main

---

## Support

For technical support or questions:
- Check the documentation files
- Review the changelog for recent updates
- Contact the development team

---

## License

Proprietary - All rights reserved

---

## Version History

### January 27, 2025
- Complete customer portal implementation
- Website ordering system fixes
- Reviews and ratings system
- HR access for Finance Managers
- Various bug fixes and improvements

See [CHANGELOG_2025_01_27.md](CHANGELOG_2025_01_27.md) for detailed changelog.

