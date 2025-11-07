## 🚀 CLEAN DEPLOYMENT GUIDE - Darfiden Management System v1.0.11

### 📦 **What You're Getting**

Complete, production-ready system with ALL features:
- ✅ 48 PHP files (all tested and working)
- ✅ Fresh database schema (no migrations needed)
- ✅ All features from v1.0.0 to v1.0.11
- ✅ Complete documentation
- ✅ Ready for immediate deployment

---

### 🎯 **FRESH INSTALLATION (Recommended)**

#### **Step 1: Create Database**
```sql
CREATE DATABASE darfiden_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'darfiden_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON darfiden_db.* TO 'darfiden_user'@'localhost';
FLUSH PRIVILEGES;
```

#### **Step 2: Import Fresh Schema**
```bash
mysql -u darfiden_user -p darfiden_db < sql/FRESH_COMPLETE_SCHEMA_v1.0.11.sql
```

**What this does:**
- Creates 10 tables with all features
- Inserts default admin user (admin/admin123)
- Inserts 2 sample shops
- Sets up all relationships and constraints
- Configures indexes for performance

**Takes:** 5-10 seconds

#### **Step 3: Configure Database Connection**
Edit `src/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'darfiden_db');
define('DB_USER', 'darfiden_user');
define('DB_PASS', 'your_secure_password');
```

#### **Step 4: Set File Permissions**
```bash
# Main application
chmod 755 -R /path/to/app

# Upload directories  
mkdir -p uploads/passports uploads/winnings uploads/guarantors
chmod 775 uploads/
chmod 775 uploads/passports/
chmod 775 uploads/winnings/
chmod 775 uploads/guarantors/

# Set ownership (if applicable)
chown -R www-data:www-data /path/to/app
```

#### **Step 5: Access Application**
```
http://yourdomain.com/

Login:
Username: admin
Password: admin123

⚠️ CHANGE PASSWORD IMMEDIATELY!
```

---

### ✅ **VERIFICATION CHECKLIST**

After installation, verify:

**1. Login System:**
- [ ] Can login with admin/admin123
- [ ] Can change password
- [ ] Can logout and login again

**2. Shop Management:**
- [ ] Create new shop (with shop code)
- [ ] Edit shop
- [ ] List shops
- [ ] See 2 sample shops (SH001, SH002)

**3. Staff Management:**
- [ ] Register new staff
- [ ] Edit staff
- [ ] Add guarantor information
- [ ] Upload passport photo
- [ ] Upload guarantor photo
- [ ] Approve pending staff
- [ ] Delete staff

**4. Shop Assignments:**
- [ ] Assign staff to multiple shops
- [ ] Remove assignments
- [ ] View all assignments

**5. Daily Operations:**
- [ ] Create daily operation
- [ ] Select shop code (staff sees only assigned)
- [ ] Enter tips
- [ ] See tips calculation auto-update
- [ ] Submit operation
- [ ] View operations list

**6. Expenses:**
- [ ] Create expense
- [ ] Edit expense
- [ ] View expenses list

**7. Winnings:**
- [ ] Upload winning
- [ ] Unique ticket number validation
- [ ] Approve/Decline winning
- [ ] Search winnings

**8. Debt Management:**
- [ ] Create debt entry
- [ ] Record debt payment
- [ ] View debt list

**9. Reports:**
- [ ] Generate staff report
- [ ] See shop code grouping
- [ ] See tips calculation
- [ ] View grand totals

---

### 📊 **DATABASE STRUCTURE**

**10 Tables:**
1. **users** - User accounts, roles, guarantor info
2. **shops** - Shop management with codes
3. **staff_shop_assignments** - Multi-shop assignments
4. **assignments** - Legacy assignments
5. **daily_operations** - Daily ops with tips
6. **expenses** - Expense tracking
7. **winnings** - Winning management
8. **debts** - Staff debt tracking
9. **activity_logs** - System logging
10. **Sample data** - Default admin + 2 shops

---

### 🔧 **TROUBLESHOOTING**

**Issue: Can't login**
```
Solution: Check src/config.php database credentials
Verify: mysql -u darfiden_user -p darfiden_db
```

**Issue: Permission denied on uploads**
```bash
chmod 775 uploads/ -R
chown www-data:www-data uploads/ -R
```

**Issue: Shop codes not showing for staff**
```
Solution: Assign staff to shops via "Shop Assignments" page
Or run: php migrate_staff_assignments.php (if upgrading)
```

**Issue: Too many database connections**
```sql
SET GLOBAL max_connections = 500;
```

**Issue: White screen**
```
Check PHP error logs:
tail -f /var/log/apache2/error.log
OR
tail -f /var/log/nginx/error.log
```

---

### 🎨 **FEATURES INCLUDED**

#### **Core Features:**
- ✅ Complete authentication system
- ✅ Role-based access control (Admin, Manager, Staff)
- ✅ Session management
- ✅ Password hashing (bcrypt)

#### **Shop Management:**
- ✅ CRUD operations
- ✅ Shop codes
- ✅ Manager assignment
- ✅ Status tracking

#### **Staff Management:**
- ✅ CRUD operations
- ✅ Passport photo upload
- ✅ Guarantor information (name, address, phone, photo)
- ✅ Multi-shop assignments
- ✅ Approval system
- ✅ Status management

#### **Daily Operations:**
- ✅ Shop code-based entries
- ✅ Tips calculation (Cash Balance + Tips)
- ✅ Auto cash balance formula
- ✅ Debt tracking
- ✅ One entry per staff per shop per day
- ✅ Staff see only assigned shops

#### **Expense Management:**
- ✅ Create/Edit/List expenses
- ✅ Category tracking
- ✅ Receipt upload

#### **Winning Management:**
- ✅ Upload winnings
- ✅ Unique ticket validation
- ✅ Approval/Decline system
- ✅ Search and filter
- ✅ Pagination

#### **Debt Management:**
- ✅ Track staff debts
- ✅ Record payments
- ✅ Status tracking (pending, paid, partial)
- ✅ Balance calculation

#### **Reporting:**
- ✅ Staff performance reports
- ✅ Group by shop code
- ✅ Date range filtering
- ✅ Tips calculation included
- ✅ Grand totals

#### **Admin Features:**
- ✅ Create operations for any staff
- ✅ See all shops
- ✅ Approve staff and winnings
- ✅ Manage assignments
- ✅ Full system access

---

### 📁 **FILE STRUCTURE**

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
│   ├── config.php (CONFIGURE THIS)
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
│       ├── debt.php
│       ├── reports.php
│       ├── assign.php
│       └── staff_assignment.php
├── sql/
│   └── FRESH_COMPLETE_SCHEMA_v1.0.11.sql (USE THIS)
├── [48 PHP pages]
└── [Documentation files]
```

---

### 🔐 **SECURITY BEST PRACTICES**

**After Deployment:**

1. **Change Default Password**
   - Login as admin
   - Go to Change Password
   - Set strong password

2. **Database Security**
   - Use strong database password
   - Don't use 'root' user
   - Limit database user permissions

3. **File Permissions**
   - Code: 755 for directories, 644 for files
   - Uploads: 775 for directories
   - Config: Protect src/config.php

4. **Enable HTTPS**
   - Get SSL certificate
   - Force HTTPS in production
   - Secure session cookies

5. **Regular Backups**
   ```bash
   # Daily backup cron
   0 2 * * * mysqldump -u user -p darfiden_db > backup_$(date +\%Y\%m\%d).sql
   ```

6. **Update PHP**
   - Use PHP 8.0+ if possible
   - Keep PHP updated
   - Enable security extensions

7. **Monitor Logs**
   - Check activity_logs table
   - Monitor error logs
   - Watch for suspicious activity

---

### 📊 **SYSTEM REQUIREMENTS**

**Minimum:**
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite OR Nginx
- 50MB disk space
- 128MB PHP memory_limit

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- 256MB PHP memory_limit
- SSL certificate
- Redis/Memcached (for sessions)

**Required PHP Extensions:**
- PDO
- PDO_MySQL
- mbstring
- fileinfo
- gd or imagick
- json

---

### 🎯 **PRODUCTION DEPLOYMENT CHECKLIST**

**Pre-Deployment:**
- [ ] Test on staging environment
- [ ] Backup existing data (if upgrading)
- [ ] Review database credentials
- [ ] Test file upload limits
- [ ] Verify PHP version and extensions

**Deployment:**
- [ ] Upload all files
- [ ] Import FRESH_COMPLETE_SCHEMA_v1.0.11.sql
- [ ] Configure src/config.php
- [ ] Set file permissions
- [ ] Test uploads directories

**Post-Deployment:**
- [ ] Login and test all features
- [ ] Change default admin password
- [ ] Create additional users
- [ ] Create real shops and staff
- [ ] Test daily operations flow
- [ ] Verify reports generation

**Security:**
- [ ] Enable HTTPS
- [ ] Set up backups
- [ ] Configure max_connections
- [ ] Set secure file permissions
- [ ] Review error_log settings

---

### 🆘 **SUPPORT & DOCUMENTATION**

**Documentation Files:**
- `README_GITHUB.md` - Complete guide
- `UPDATE_v1.0.*.txt` - Feature notes
- `DEPLOYMENT_PACKAGE_v1.0.8.txt` - Detailed deployment
- `DATABASE_MISMATCH_FIX.md` - Troubleshooting
- `FIXES_APPLIED.md` - Bug fix history

**Quick Links:**
- Login: http://yourdomain.com/
- Dashboard: http://yourdomain.com/index.php
- Admin: admin / admin123 (change immediately)

---

### 🎊 **WHAT'S NEW IN v1.0.11**

- ✅ Complete fresh schema with all features
- ✅ Fixed assignment constraint issues
- ✅ Fixed delete staff errors
- ✅ Guarantor fields in main schema
- ✅ All v1.0.9 features in main schema
- ✅ Clean deployment ready
- ✅ No migrations needed

---

### ✅ **SUCCESS INDICATORS**

Your deployment is successful when:

1. ✅ You can login without errors
2. ✅ All pages load correctly
3. ✅ You can create daily operations
4. ✅ Shop codes show in dropdown
5. ✅ Tips calculation works
6. ✅ Staff can see only assigned shops
7. ✅ Admin can create operations for any staff
8. ✅ Reports generate correctly
9. ✅ File uploads work
10. ✅ No SQL errors in logs

---

**🎉 DEPLOYMENT COMPLETE! Your system is ready for production use!**

**Default Login:** admin / admin123 (⚠️ CHANGE IMMEDIATELY)

**Next Steps:**
1. Change admin password
2. Create shops with codes
3. Create staff members
4. Assign staff to shops
5. Start using the system!
