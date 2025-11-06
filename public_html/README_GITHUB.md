# Darfiden Management System

A comprehensive PHP/MySQL management system for shop operations, staff management, daily operations tracking, and reporting.

## 🌟 Current Version: v1.0.9

### Latest Features (v1.0.9)
- ✅ Multi-shop staff assignments
- ✅ Shop code-based daily operations
- ✅ Tips calculation and tracking
- ✅ Enhanced reports with shop code grouping
- ✅ Staff guarantor information system
- ✅ Staff debt management
- ✅ Winning/staff approval systems

## 📋 System Requirements

### Minimum Requirements
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ (with mod_rewrite) or Nginx
- 50MB disk space
- 128MB PHP memory_limit

### Recommended Requirements
- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.6+
- 256MB PHP memory_limit
- SSL certificate for HTTPS

### Required PHP Extensions
- PDO
- PDO_MySQL
- mbstring
- fileinfo
- gd or imagick
- json

## 🚀 Installation

### Step 1: Clone Repository
```bash
git clone https://github.com/adeyinkasbayo/dede.git
cd dede
```

### Step 2: Create Database
```sql
CREATE DATABASE darfiden_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'darfiden_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON darfiden_db.* TO 'darfiden_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 3: Import Database Schema
```bash
mysql -u darfiden_user -p darfiden_db < sql/darfiden_full_schema.sql
```

### Step 4: Configure Database Connection
Edit `src/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'darfiden_db');
define('DB_USER', 'darfiden_user');
define('DB_PASS', 'your_secure_password');
```

### Step 5: Set File Permissions
```bash
chmod 755 -R /path/to/dede
chmod 775 uploads/ uploads/passports/ uploads/winnings/ uploads/guarantors/
```

OR with proper ownership:
```bash
chown -R www-data:www-data /path/to/dede
chmod 755 -R /path/to/dede
chmod 775 uploads/ uploads/passports/ uploads/winnings/ uploads/guarantors/
```

### Step 6: Access Application
Navigate to: `http://yourdomain.com/`

**Default Login:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the default password immediately after first login!

## 📊 Key Features

### Shop Management
- Create, edit, and manage multiple shops
- Assign shop codes for easy identification
- Track shop-specific operations

### Staff Management
- Complete staff CRUD operations
- Multi-shop assignments (staff can work at multiple locations)
- Staff guarantor information (name, address, phone, photo)
- Staff approval system
- Role-based access control (Admin, Manager, Staff)

### Daily Operations
- Shop code-based operations
- Opening/closing balance tracking
- Expense and winning tracking
- Tips calculation: Cash Balance + Tips
- Automatic cash balance calculation
- Debt management integration
- One entry per staff per shop code per day

### Tips Management
- Track tips per operation
- Automatic tips calculation
- Tips visible in daily operations and reports
- Independent tracking from cash balance

### Reporting
- Staff performance reports by date range
- Automatic grouping by shop code
- Shows cash balance per shop code
- Daily totals for multi-shop operations
- Grand totals across entire period
- Tips calculation included in all reports

### Winning Management
- Upload winning receipts with images
- Ticket number uniqueness validation
- Search and filter by date/month/ticket
- Approval/decline system for managers
- Pagination for large datasets

### Debt Management
- Track staff debts by date/range
- Debt payment recording
- Integration with daily operations
- Updated cash balance formulas

### Security Features
- Password hashing (bcrypt)
- Input sanitization
- SQL injection protection
- Role-based access control
- Session management
- Activity logging

## 📂 Project Structure

```
darfiden/
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
│   ├── config.php (configure this)
│   ├── init.php
│   ├── auth.php
│   ├── helpers.php
│   ├── permissions.php
│   └── controllers/
│       ├── auth_controller.php
│       ├── shop.php
│       ├── user.php
│       ├── daily.php
│       ├── expenses.php
│       ├── winnings.php
│       ├── reports.php
│       └── staff_assignment.php
├── sql/
│   ├── darfiden_full_schema.sql (MAIN - import this)
│   ├── migration_v1.0.3_add_winnings_cashbalance.sql
│   ├── migration_v1.0.6_approval_system.sql
│   ├── migration_v1.0.8_add_guarantor_to_staff.sql
│   ├── migration_v1.0.9_multi_shop_tips.sql
│   └── debts_table.sql
└── [Various PHP pages]
```

## 🔄 Upgrading from Previous Versions

### From v1.0.8 to v1.0.9
```bash
# 1. Backup database
mysqldump -u username -p darfiden_db > backup_v1.0.8.sql

# 2. Pull latest code
git pull origin main

# 3. Run migration
mysql -u username -p darfiden_db < sql/migration_v1.0.9_multi_shop_tips.sql

# 4. Clear cache (if applicable)
# 5. Test functionality
```

## 📖 Documentation

Complete documentation is available in the following files:
- `DEPLOYMENT_GUIDE.txt` - General deployment instructions
- `UPDATE_v1.0.9.txt` - Latest version release notes
- `UPDATE_v1.0.8.txt` - Guarantor system documentation
- `DEPLOYMENT_PACKAGE_v1.0.8.txt` - Complete v1.0.8 guide
- `FILE_INDEX.txt` - Complete file structure reference
- Various `HOTFIX_*.txt` and `UPDATE_*.txt` files for version history

## 🔧 Configuration

### Database Configuration
Edit `src/config.php` to configure database connection.

### Application Settings
```php
// src/config.php
define('APP_NAME', 'Darfiden Management');
define('APP_VERSION', '1.0.9');
define('SESSION_TIMEOUT', 3600); // 1 hour
```

## 📊 Cash Balance Formulas

### Cash Balance
```
Opening Balance + Transfer to Staff - Total Winnings - Total Expenses - Daily Debt - Closing Balance = Cash Balance
```

### Tips Calculation
```
Cash Balance + Tips = Tips Calculation
```

## 🛡️ Security Best Practices

1. **Change Default Credentials:** Immediately change `admin/admin123` after first login
2. **Use Strong Passwords:** Enforce strong password policies for all users
3. **Secure config.php:** Ensure `src/config.php` has restricted permissions
4. **Enable HTTPS:** Use SSL/TLS certificates in production
5. **Regular Backups:** Schedule automatic database backups
6. **Keep Updated:** Regularly check for updates and security patches
7. **File Permissions:** Set appropriate file and directory permissions
8. **Monitor Logs:** Regularly review activity logs for suspicious activity

## 🐛 Troubleshooting

### Database Connection Failed
- Verify database credentials in `src/config.php`
- Check if MySQL service is running
- Ensure database user has proper permissions

### Permission Denied on Uploads
```bash
chmod 775 uploads/ uploads/passports/ uploads/winnings/ uploads/guarantors/
chown www-data:www-data uploads/ -R
```

### Blank Page After Login
- Check PHP error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Enable error display temporarily in `src/config.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

### SQL Errors During Migration
- Check if migration was already applied
- Verify database schema version
- Review migration script for compatibility

## 📝 Version History

- **v1.0.9** (Nov 2025) - Multi-shop assignments, tips management, enhanced reports
- **v1.0.8** (Nov 2025) - Staff guarantor information system
- **v1.0.7** - Staff debt management
- **v1.0.6** - Approval systems (staff & winnings)
- **v1.0.5** - Enhanced winnings management
- **v1.0.4** - Bug fix (finfo class error)
- **v1.0.3** - Daily operations cash balance
- **v1.0.2** - Bug fix (foreign key constraints)
- **v1.0.1** - Bug fix (get_current_user conflict)
- **v1.0.0** - Initial release

## 📞 Support

For issues, questions, or contributions:
- Review documentation files in the repository
- Check `TROUBLESHOOTING` section above
- Review closed issues on GitHub

## 📄 License

Copyright © 2025 Darfiden Management System. All rights reserved.

## 🙏 Acknowledgments

Built with:
- PHP
- MySQL/MariaDB
- Bootstrap CSS Framework
- Font Awesome Icons
- JavaScript (Vanilla)

---

**Note:** This system is designed for internal business operations. Ensure proper security measures are in place before deploying to production.
