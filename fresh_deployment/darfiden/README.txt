================================================================================
DARFIDEN MANAGEMENT SYSTEM - COMPLETE PACKAGE v1.0.11
================================================================================

🎉 FRESH, CLEAN, READY-TO-DEPLOY PACKAGE

This package contains EVERYTHING you need for a complete, working system.
No migrations, no errors, no complications - just deploy and use!

================================================================================
📦 WHAT'S INCLUDED
================================================================================

✅ 48 PHP Files (all tested and working)
✅ Complete Database Schema (database.sql)
✅ All Controllers and Models
✅ Complete UI (HTML/CSS/JS)
✅ File Upload System
✅ Documentation
✅ Default Admin Account

================================================================================
🚀 QUICK START (5 MINUTES)
================================================================================

STEP 1: CREATE DATABASE
------------------------
mysql -u root -p

CREATE DATABASE darfiden_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'darfiden_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON darfiden_db.* TO 'darfiden_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

STEP 2: IMPORT DATABASE
-----------------------
mysql -u darfiden_user -p darfiden_db < database.sql

STEP 3: CONFIGURE APPLICATION
-----------------------------
Edit: src/config.php

Change these lines:
define('DB_HOST', 'localhost');
define('DB_NAME', 'darfiden_db');
define('DB_USER', 'darfiden_user');
define('DB_PASS', 'your_password');

STEP 4: SET PERMISSIONS
-----------------------
chmod 755 -R /path/to/darfiden
chmod 775 uploads/ -R

Or with ownership:
chown -R www-data:www-data /path/to/darfiden
chmod 755 -R /path/to/darfiden
chmod 775 uploads/ -R

STEP 5: ACCESS APPLICATION
--------------------------
http://yourdomain.com/

Login:
Username: admin
Password: admin123

⚠️  CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN!

================================================================================
✨ FEATURES
================================================================================

USER MANAGEMENT:
- Registration & authentication
- Role-based access (Admin, Manager, Staff)
- Passport photo upload
- Guarantor information (name, address, phone, photo)
- Approval system
- Status management

SHOP MANAGEMENT:
- CRUD operations
- Shop codes (SH001, SH002, etc.)
- Manager assignment
- Location tracking

MULTI-SHOP ASSIGNMENTS:
- Assign staff to multiple shops
- Staff see only their assigned shops
- Admin/Manager can manage all

DAILY OPERATIONS:
- Shop code-based entries
- Tips calculation (Cash Balance + Tips)
- Automatic cash balance formula
- Debt tracking
- One entry per staff per shop per day

EXPENSE MANAGEMENT:
- Track expenses by category
- Receipt upload
- Date filtering

WINNING MANAGEMENT:
- Upload winnings with receipts
- Unique ticket number validation
- Approval/Decline system
- Search and pagination

DEBT MANAGEMENT:
- Track staff debts
- Record payments
- Status tracking

REPORTS:
- Staff performance reports
- Group by shop code
- Date range filtering
- Tips calculation
- Grand totals

================================================================================
📊 DATABASE STRUCTURE
================================================================================

9 Tables:
1. users - User accounts with guarantor info
2. shops - Shop management with codes
3. staff_shop_assignments - Multi-shop assignments
4. daily_operations - Daily ops with tips
5. expenses - Expense tracking
6. winnings - Winning management
7. debts - Staff debt tracking
8. assignments - Legacy assignments
9. activity_logs - System logging

Default Data:
- Admin user (admin/admin123)
- 2 Sample shops (SH001, SH002)

================================================================================
📁 FILE STRUCTURE
================================================================================

darfiden/
├── database.sql (IMPORT THIS FIRST)
├── README.txt (YOU ARE HERE)
├── assets/
│   ├── css/styles.css
│   └── js/app.js
├── uploads/
│   ├── passports/
│   ├── winnings/
│   └── guarantors/
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   └── messages.php
├── src/
│   ├── config.php (CONFIGURE THIS)
│   ├── init.php
│   ├── auth.php
│   ├── helpers.php
│   ├── permissions.php
│   └── controllers/
└── [48 PHP pages]

================================================================================
🔧 SYSTEM REQUIREMENTS
================================================================================

MINIMUM:
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite OR Nginx
- 50MB disk space
- 128MB PHP memory_limit

RECOMMENDED:
- PHP 8.0+
- MySQL 8.0+
- 256MB PHP memory_limit
- SSL certificate

REQUIRED PHP EXTENSIONS:
- PDO
- PDO_MySQL
- mbstring
- fileinfo
- gd or imagick
- json

================================================================================
✅ VERIFICATION CHECKLIST
================================================================================

After installation:

[ ] Can access http://yourdomain.com/
[ ] Can login with admin/admin123
[ ] Dashboard loads without errors
[ ] Can create new shop
[ ] Can create new staff
[ ] Can assign staff to shop
[ ] Can create daily operation
[ ] Shop codes show in dropdown
[ ] Tips calculation works
[ ] Can upload files
[ ] Reports generate correctly

================================================================================
🔐 SECURITY
================================================================================

AFTER INSTALLATION:

1. Change default admin password immediately
2. Use strong database password
3. Set proper file permissions
4. Enable HTTPS in production
5. Keep PHP and MySQL updated
6. Regular database backups

FILE PERMISSIONS:
- Application files: 755 (directories), 644 (files)
- Upload directories: 775
- Config file: 644 (protect with .htaccess)

================================================================================
🆘 TROUBLESHOOTING
================================================================================

ISSUE: Can't login
SOLUTION: Check src/config.php database credentials

ISSUE: Permission denied on uploads
SOLUTION: chmod 775 uploads/ -R

ISSUE: White screen/blank page
SOLUTION: Check PHP error log, enable display_errors temporarily

ISSUE: SQL errors
SOLUTION: Verify database.sql was imported correctly

ISSUE: Shop codes not showing
SOLUTION: Assign staff to shops via "Shop Assignments" page

================================================================================
📞 SUPPORT
================================================================================

Documentation Files:
- README.txt (this file)
- Check PHP error logs
- Check MySQL error logs

Common Paths:
- Apache: /var/log/apache2/error.log
- Nginx: /var/log/nginx/error.log
- PHP-FPM: /var/log/php-fpm/error.log

================================================================================
🎯 FORMULAS
================================================================================

CASH BALANCE:
Opening Balance + Transfer to Staff - Total Winnings - Total Expenses 
- Daily Debt - Closing Balance = Cash Balance

TIPS CALCULATION:
Cash Balance + Tips = Tips Calculation

================================================================================
🎊 VERSION INFO
================================================================================

Version: 1.0.11 (Final Clean Release)
Release Date: November 2025
Package Type: Complete Fresh Installation

FEATURES INCLUDED:
- All features from v1.0.0 through v1.0.11
- User management with guarantor info
- Multi-shop staff assignments
- Daily operations with tips
- Complete reporting system
- All security features

================================================================================
📝 QUICK COMMANDS REFERENCE
================================================================================

CREATE DATABASE:
mysql -u root -p < database.sql

CHECK TABLES:
mysql -u username -p darfiden_db -e "SHOW TABLES;"

TEST CONNECTION:
mysql -u darfiden_user -p darfiden_db

SET PERMISSIONS:
chmod 755 -R /path/to/darfiden
chmod 775 uploads/ -R

RESTART SERVICES:
sudo systemctl restart apache2
sudo systemctl restart mysql

================================================================================
✅ SUCCESS INDICATORS
================================================================================

Your installation is successful when:

✅ You can login without errors
✅ All pages load correctly
✅ You can create shops with codes
✅ You can create and assign staff
✅ Daily operations can be created
✅ Shop codes show in dropdown for staff
✅ Tips calculation auto-updates
✅ Reports generate correctly
✅ File uploads work
✅ No SQL errors in logs

================================================================================
🎉 DEPLOYMENT COMPLETE!
================================================================================

Your Darfiden Management System is ready to use!

Default Login:
Username: admin
Password: admin123

⚠️  CHANGE PASSWORD IMMEDIATELY!

Next Steps:
1. Change admin password
2. Create your real shops
3. Create staff members
4. Assign staff to shops
5. Start using the system!

================================================================================
Thank you for using Darfiden Management System!
================================================================================
