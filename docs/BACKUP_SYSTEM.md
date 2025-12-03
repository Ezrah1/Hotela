# Backup System Documentation

## Overview

The Hotela backup system provides automated backup capabilities for:
- **Database**: Full MySQL database dump
- **Files**: Configuration, migrations, uploads, and storage files

## Backup Scripts

### 1. Full Backup (`scripts/backup.php`)

Creates a complete backup of database and files.

**Usage:**
```bash
# Full backup (database + files)
php scripts/backup.php

# Database only
php scripts/backup.php --database-only

# Files only
php scripts/backup.php --files-only

# Custom output directory
php scripts/backup.php --output-dir=/path/to/backups
```

**What it backs up:**
- Database: Complete MySQL dump (.sql file)
- Config files: `config/` directory
- Storage: `storage/` directory
- Migrations: `database/migrations/` directory
- Uploads: `public/uploads/` directory

**Output:**
- Creates timestamped directory: `hotela_backup_YYYY-MM-DD_HH-MM-SS/`
- Database file: `database_YYYY-MM-DD_HH-MM-SS.sql`
- Files directory with all backed up files
- Backup info file: `backup_info.txt`
- Optional ZIP archive (if zip extension available)

### 2. Quick Database Backup (`scripts/backup_database.php`)

Quick database-only backup.

**Usage:**
```bash
php scripts/backup_database.php
```

**Output:**
- Single SQL file: `hotela_db_YYYY-MM-DD_HH-MM-SS.sql`
- Stored in backup directory (default: `C:\Users\USERNAME\Desktop\Backups`)

## Backup Location

**Default Location:**
- Windows: `C:\Users\USERNAME\Desktop\Backups`
- Linux/Mac: `~/Backups`

**Custom Location:**
Set environment variable:
```bash
export BACKUP_DIR=/path/to/backups
php scripts/backup.php
```

Or use `--output-dir` option:
```bash
php scripts/backup.php --output-dir=/path/to/backups
```

## Requirements

### Database Backup
- MySQL/MariaDB server running
- `mysqldump` command available
- Database credentials in `config/app.php` or `.env`

**Finding mysqldump:**
- Windows (XAMPP): `C:\xampp\mysql\bin\mysqldump.exe`
- Windows (MySQL): `C:\Program Files\MySQL\MySQL Server X.X\bin\mysqldump.exe`
- Linux/Mac: Usually in PATH or `/usr/bin/mysqldump`

### File Backup
- Read access to application directories
- Write access to backup directory

### Archive Creation (Optional)
- PHP `zip` extension enabled

## Automated Backups

### Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Set trigger (daily, weekly, etc.)
4. Action: Start a program
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `C:\xampp\htdocs\Hotela\scripts\backup.php`
7. Start in: `C:\xampp\htdocs\Hotela`

### Linux Cron

Add to crontab (`crontab -e`):
```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /path/to/Hotela/scripts/backup.php

# Weekly full backup on Sunday at 1 AM
0 1 * * 0 /usr/bin/php /path/to/Hotela/scripts/backup.php
```

## Restoring Backups

### Database Restore

```bash
# Using MySQL command line
mysql -u root -p hotela < backup_file.sql

# Or using XAMPP MySQL
C:\xampp\mysql\bin\mysql.exe -u root -p hotela < backup_file.sql
```

### Files Restore

Simply copy files from backup directory back to application:
```bash
# Restore config
cp -r backup/files/config/* /path/to/Hotela/config/

# Restore uploads
cp -r backup/files/public/uploads/* /path/to/Hotela/public/uploads/
```

## Backup Verification

### Check Backup File

```bash
# Check if SQL file is valid
head -n 20 backup_file.sql

# Should see MySQL dump header:
# -- MySQL dump 10.13  Distrib X.X.X, for Win64 (x86_64)
# -- Host: localhost    Database: hotela
```

### Check File Integrity

```bash
# Check backup info
cat backup/files/backup_info.txt

# Verify file counts
find backup/files -type f | wc -l
```

## Best Practices

1. **Regular Backups**
   - Daily database backups
   - Weekly full backups
   - Before major updates

2. **Backup Retention**
   - Keep last 7 daily backups
   - Keep last 4 weekly backups
   - Keep monthly backups for 3 months

3. **Offsite Storage**
   - Copy backups to external drive
   - Upload to cloud storage
   - Use version control for code

4. **Test Restores**
   - Periodically test restore process
   - Verify backup integrity
   - Document restore procedures

5. **Security**
   - Secure backup directory permissions
   - Encrypt sensitive backups
   - Don't store backups in web-accessible directories

## Troubleshooting

### mysqldump Not Found

**Windows:**
- Add MySQL bin directory to PATH
- Or specify full path in script
- Check XAMPP installation

**Linux/Mac:**
```bash
which mysqldump
# If not found, install MySQL client
sudo apt-get install mysql-client  # Ubuntu/Debian
brew install mysql-client           # Mac
```

### Permission Denied

```bash
# Check directory permissions
ls -la /path/to/backup/dir

# Fix permissions
chmod 755 /path/to/backup/dir
```

### Backup File Empty

- Check database credentials
- Verify database exists
- Check MySQL server is running
- Review error output from script

### Large Backup Files

- Use compression (ZIP archive)
- Exclude unnecessary tables
- Use incremental backups for large databases

## Backup Script Output

### Successful Backup
```
=== Hotela Backup Script ===
Backup directory: C:\Users\User\Desktop\Backups\hotela_backup_2025-01-22_14-30-00

✓ Created backup directory

1. Backing up database...
------------------------
Running: mysqldump hotela...
✓ Database backup created: database_2025-01-22_14-30-00.sql (1250.50 KB)

2. Backing up files...
---------------------
✓ Backed up config (Configuration files)
✓ Backed up storage (Storage and uploads)
✓ Backed up database/migrations (Database migrations)
✓ Backed up public/uploads (Public uploads)

✓ Backed up 4 directories
✓ Created backup info file

3. Creating archive...
---------------------
✓ Archive created: hotela_backup_2025-01-22_14-30-00.zip (2.45 MB)

=== Backup Summary ===
Backup location: C:\Users\User\Desktop\Backups\hotela_backup_2025-01-22_14-30-00

✅ Backup completed successfully!
```

## Related Files

- `scripts/backup.php` - Full backup script
- `scripts/backup_database.php` - Quick database backup
- `app/Services/AutoUpdateService.php` - Auto-update backup functionality

---

**Last Updated:** 2025-01-22

