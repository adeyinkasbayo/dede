# 🚨 CRITICAL: Database Schema Mismatch - Complete Fix Guide

## 🎯 THE PROBLEM

You're running **NEW CODE** (v1.0.9+) against **OLD DATABASE** schema.

**Errors You're Seeing:**
1. ❌ Assignment removal: Duplicate constraint error
2. ❌ Daily operations: Column 'shop_code' not found
3. ❌ Delete staff: Prepared statement error
4. ❌ Too many database connections

**Root Cause:** Database schema is missing:
- `shop_code` column in daily_operations
- `tips`, `tips_calculation` columns
- `staff_shop_assignments` table
- Updated constraints and indexes
- Guarantor columns in users table

---

## ✅ SOLUTION: Two Options

### 🔄 OPTION 1: Fresh Database Install (RECOMMENDED - FASTEST)

**⚠️ WARNING: This will DELETE all existing data!**

**Step 1: Backup Current Database**
```bash
mysqldump -u username -p darfiden_db > backup_before_fix_$(date +%Y%m%d_%H%M%S).sql
```

**Step 2: Drop and Recreate Database**
```sql
DROP DATABASE IF EXISTS darfiden_db;
CREATE DATABASE darfiden_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE darfiden_db;
```

**Step 3: Import Complete Schema**
```bash
mysql -u username -p darfiden_db < sql/darfiden_full_schema.sql
```

**Step 4: Create Admin User**
```bash
php create_admin.php
```
OR manually:
```sql
INSERT INTO users (username, password, full_name, role, status) 
VALUES ('admin', '$2y$10$YourHashedPassword', 'Admin User', 'admin', 'active');
```

**Step 5: Test**
- Login as admin
- Create shops
- Create staff
- Test all features

**✅ Result:** Clean database with all features working

---

### 🔧 OPTION 2: Run All Migrations (Keep Existing Data)

**⚠️ Must run migrations IN ORDER!**

**Step 1: Backup Database**
```bash
mysqldump -u username -p darfiden_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Step 2: Run Migrations in Order**

```bash
# Migration 1: Add winnings and cash balance columns
mysql -u username -p darfiden_db < sql/migration_v1.0.3_add_winnings_cashbalance.sql

# Migration 2: Add approval system
mysql -u username -p darfiden_db < sql/migration_v1.0.6_approval_system.sql

# Migration 3: Add guarantor fields
mysql -u username -p darfiden_db < sql/migration_v1.0.8_add_guarantor_to_staff.sql

# Migration 4: Add multi-shop and tips
mysql -u username -p darfiden_db < sql/migration_v1.0.9_multi_shop_tips.sql

# Hotfix: Fix assignment constraint
mysql -u username -p darfiden_db < sql/hotfix_v1.0.11_remove_duplicate_constraint.sql
```

**Step 3: Verify Schema**
```sql
USE darfiden_db;

-- Check users table has guarantor columns
SHOW COLUMNS FROM users LIKE 'guarantor%';
-- Should return 4 rows

-- Check daily_operations has new columns
SHOW COLUMNS FROM daily_operations LIKE 'shop_code';
SHOW COLUMNS FROM daily_operations LIKE 'tips%';
-- Should return columns

-- Check staff_shop_assignments exists
SHOW TABLES LIKE 'staff_shop_assignments';
-- Should return 1 row

-- Check constraints
SHOW CREATE TABLE staff_shop_assignments;
-- Should NOT have unique_staff_shop constraint with status
```

**Step 4: Run Migration Script**
```bash
# Navigate to your application
php migrate_staff_assignments.php
# This migrates old shop assignments to new system
```

**✅ Result:** Updated database keeping all existing data

---

## 🔍 VERIFY WHICH MIGRATIONS YOU NEED

**Check what's missing:**

```sql
USE darfiden_db;

-- Check 1: Do you have shop_code column?
SHOW COLUMNS FROM daily_operations LIKE 'shop_code';
-- If empty, need migration v1.0.9

-- Check 2: Do you have guarantor columns?
SHOW COLUMNS FROM users LIKE 'guarantor%';
-- If empty, need migration v1.0.8

-- Check 3: Does staff_shop_assignments table exist?
SHOW TABLES LIKE 'staff_shop_assignments';
-- If empty, need migration v1.0.9

-- Check 4: What constraints exist?
SHOW CREATE TABLE staff_shop_assignments\G
-- If you see 'unique_staff_shop' with status, need hotfix v1.0.11
```

---

## 🚨 FIX "TOO MANY CONNECTIONS" ERROR

This is a separate issue - your database has connection leaks.

**Immediate Fix:**
```sql
-- Kill idle connections
SHOW PROCESSLIST;
-- Note connection IDs, then:
KILL [connection_id];

-- Increase max connections temporarily
SET GLOBAL max_connections = 500;
```

**Permanent Fix in MySQL config:**
```ini
# Edit /etc/mysql/my.cnf or /etc/my.cnf
[mysqld]
max_connections = 500
wait_timeout = 600
interactive_timeout = 600
```

**Fix in Application:**
Ensure PDO connections are properly closed. Check `src/config.php`:
```php
// Add after PDO creation
$pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
```

---

## 📋 QUICK FIX SCRIPT (All Migrations at Once)

Create file: `run_all_migrations.sh`

```bash
#!/bin/bash

# Configuration
DB_USER="your_username"
DB_NAME="darfiden_db"

echo "🔄 Running all migrations..."

# Backup first
echo "📦 Creating backup..."
mysqldump -u $DB_USER -p $DB_NAME > backup_before_migrations_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
echo "🔧 Running migration v1.0.3..."
mysql -u $DB_USER -p $DB_NAME < sql/migration_v1.0.3_add_winnings_cashbalance.sql

echo "🔧 Running migration v1.0.6..."
mysql -u $DB_USER -p $DB_NAME < sql/migration_v1.0.6_approval_system.sql

echo "🔧 Running migration v1.0.8..."
mysql -u $DB_USER -p $DB_NAME < sql/migration_v1.0.8_add_guarantor_to_staff.sql

echo "🔧 Running migration v1.0.9..."
mysql -u $DB_USER -p $DB_NAME < sql/migration_v1.0.9_multi_shop_tips.sql

echo "🔧 Running hotfix v1.0.11..."
mysql -u $DB_USER -p $DB_NAME < sql/hotfix_v1.0.11_remove_duplicate_constraint.sql

echo "✅ All migrations complete!"
echo "🧪 Verifying schema..."

# Verify
mysql -u $DB_USER -p $DB_NAME -e "SHOW COLUMNS FROM daily_operations LIKE 'shop_code';"
mysql -u $DB_USER -p $DB_NAME -e "SHOW COLUMNS FROM users LIKE 'guarantor%';"
mysql -u $DB_USER -p $DB_NAME -e "SHOW TABLES LIKE 'staff_shop_assignments';"

echo "✅ Verification complete!"
```

**Run it:**
```bash
chmod +x run_all_migrations.sh
./run_all_migrations.sh
```

---

## 🎯 RECOMMENDED APPROACH

**For Production with Existing Data:**
1. ✅ Backup database
2. ✅ Run Option 2 (migrations in order)
3. ✅ Fix connection limit
4. ✅ Test thoroughly

**For Development/Testing:**
1. ✅ Option 1 (fresh install)
2. ✅ Much faster and cleaner
3. ✅ Import test data after

---

## ✅ VERIFICATION AFTER FIX

**Test Each Feature:**

1. **Daily Operations:**
   ```
   - Go to Daily Operations → Add New
   - Select shop code ← Should work
   - Fill in tips field ← Should work
   - Submit ← Should work
   ```

2. **Staff Management:**
   ```
   - Go to Staff List
   - Delete a staff member ← Should work
   - Edit staff → Add guarantor info ← Should work
   ```

3. **Shop Assignments:**
   ```
   - Go to Shop Assignments
   - Assign staff to shop ← Should work
   - Remove assignment ← Should work
   - Re-assign same staff ← Should work
   ```

4. **Database Connections:**
   ```sql
   SHOW STATUS LIKE 'Threads_connected';
   -- Should be reasonable number (< 50)
   ```

---

## 🆘 IF ERRORS PERSIST

**1. Check Schema Version:**
```sql
SELECT * FROM users LIMIT 1\G
-- Should show guarantor columns

SELECT * FROM daily_operations LIMIT 1\G
-- Should show shop_code, tips columns
```

**2. Check Constraints:**
```sql
SHOW CREATE TABLE staff_shop_assignments\G
-- Should NOT show unique constraint with status
```

**3. Check Connections:**
```sql
SHOW PROCESSLIST;
-- If showing 100+ connections, you have a leak
```

**4. Clear PHP Statement Cache:**
```bash
# Restart PHP-FPM or Apache
sudo systemctl restart php-fpm
# OR
sudo systemctl restart apache2
```

---

## 📞 SUPPORT

If migrations fail, you'll see specific error messages. Common ones:

**"Table already exists"**
- Safe to ignore, means migration was partially run

**"Column already exists"**
- Safe to ignore, means column was added before

**"Duplicate entry"**
- Not safe, indicates data conflict
- Share the full error for specific fix

---

## 🎊 FINAL CHECKLIST

After running migrations:

- [ ] No SQL errors in application
- [ ] Daily operations can be created
- [ ] Staff can be deleted
- [ ] Assignments can be removed
- [ ] Guarantor fields visible
- [ ] Tips calculation works
- [ ] Shop codes show in dropdown
- [ ] Database connections < 50
- [ ] All pages load without errors

---

**Choose your fix option and run it now. The database MUST match the code version!**
