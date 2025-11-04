# Darfiden Management System

A complete PHP + MySQL shop and staff management system.

## Features

- ✅ Complete authentication system (login, register, logout)
- ✅ Role-based access control (Admin, Manager, Staff)
- ✅ Shop management (CRUD operations)
- ✅ Staff management (CRUD operations)
- ✅ Assignment tracking
- ✅ Daily operations management
- ✅ Expenses tracking and approval
- ✅ Winning receipts upload system
- ✅ Staff performance reports
- ✅ File upload handling (passports, winning receipts)
- ✅ Responsive design with Bootstrap
- ✅ Security features (.htaccess, SQL injection protection)

## Installation

### Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (Apache)

### Steps

1. **Upload files to your domain**
   - Upload all files from the `public_html` directory to your web server's public directory
   - Common locations: `/public_html`, `/www`, `/htdocs`

2. **Create Database**
   - Create a new MySQL database
   - Import the SQL schema: `sql/darfiden_full_schema.sql`
   - You can use phpMyAdmin or command line:
     ```bash
     mysql -u your_username -p your_database_name < sql/darfiden_full_schema.sql
     ```

3. **Configure Database Connection**
   - Edit `src/config.php`
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');        // Your database host
     define('DB_NAME', 'darfiden_db');      // Your database name
     define('DB_USER', 'root');             // Your database username
     define('DB_PASS', '');                 // Your database password
     ```

4. **Set File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/passports/
   chmod 755 uploads/winnings/
   ```

5. **Create Admin User**
   - Visit: `http://yourdomain.com/create_admin.php`
   - This will create the default admin user
   - **IMPORTANT: Delete `create_admin.php` after running it**

6. **Login**
   - Visit: `http://yourdomain.com/login.php`
   - Username: `admin`
   - Password: `admin123`
   - **Change the password immediately after first login**

## Default Credentials

- **Username:** admin
- **Password:** admin123

⚠️ **Important:** Change these credentials immediately after installation!

## Directory Structure

```
public_html/
├── assets/
│   ├── css/
│   │   └── styles.css       # Main stylesheet
│   └── js/
│       └── app.js           # JavaScript functions
├── uploads/
│   ├── passports/          # Staff passport photos
│   └── winnings/           # Winning receipt images
├── includes/
│   ├── header.php          # Page header
│   ├── sidebar.php         # Navigation sidebar
│   └── messages.php        # Alert messages
├── src/
│   ├── config.php          # Configuration settings
│   ├── init.php            # Initialization & database
│   ├── auth.php            # Authentication functions
│   ├── helpers.php         # Helper functions
│   ├── permissions.php     # Permission system
│   └── controllers/        # Business logic controllers
│       ├── auth_controller.php
│       ├── shop.php
│       ├── user.php
│       ├── assign.php
│       ├── daily.php
│       ├── expenses.php
│       ├── winnings.php
│       └── reports.php
├── sql/
│   └── darfiden_full_schema.sql  # Database schema
├── .htaccess               # Apache configuration
├── index.php               # Dashboard
├── login.php               # Login page
├── register.php            # Registration page
└── [other pages]           # Various management pages
```

## User Roles

### Administrator
- Full system access
- Manage all shops
- Manage all staff (including managers)
- View all reports
- Approve expenses

### Manager
- Manage assigned shop
- Manage shop staff
- View shop reports
- Approve expenses
- Record daily operations

### Staff
- Record daily operations
- Upload winning receipts
- Create expense requests
- Upload passport photo
- Change own password

## Configuration

### Application Settings (`src/config.php`)

- **Database:** Host, name, username, password
- **Security:** Session lifetime, password salt
- **File Upload:** Max file size, allowed types
- **Timezone:** Default timezone setting

### Security Settings (`.htaccess`)

- Directory listing disabled
- Sensitive file protection
- PHP settings optimization
- Security headers

## Troubleshooting

### Database Connection Error
- Check database credentials in `src/config.php`
- Verify MySQL service is running
- Ensure database exists

### File Upload Issues
- Check folder permissions (755 for uploads/)
- Verify PHP upload_max_filesize setting
- Check available disk space

### .htaccess Errors
- Ensure mod_rewrite is enabled
- Check Apache configuration
- Verify AllowOverride is set correctly

### Permission Denied Errors
- Check file/folder permissions
- Verify web server user has access
- Review role-based permissions

## Security Recommendations

1. **Change default credentials** immediately
2. **Delete create_admin.php** after installation
3. **Use HTTPS** in production (enable in .htaccess)
4. **Regular backups** of database and files
5. **Keep PHP updated** to latest version
6. **Use strong passwords** for all accounts
7. **Limit file upload sizes** appropriately
8. **Monitor activity logs** regularly

## Support & Contact

For support or questions about this system, please contact your system administrator.

## License

This system is proprietary software. All rights reserved.

---

**Version:** 1.0  
**Last Updated:** 2025