-- COMPLETE DATABASE UPDATE TO LATEST VERSION
-- This file combines ALL migrations: v1.0.3, v1.0.6, v1.0.8, v1.0.9, and hotfix v1.0.11
-- Run this if you have the old schema and want to update to latest
-- Date: 2025

USE darfiden_db;

-- ============================================================================
-- BACKUP REMINDER
-- ============================================================================
-- IMPORTANT: Backup your database before running this script!
-- mysqldump -u username -p darfiden_db > backup_before_update.sql

START TRANSACTION;

-- ============================================================================
-- PART 1: Add winnings and cash balance columns (v1.0.3)
-- ============================================================================

-- Check and add columns to daily_operations if they don't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'daily_operations' 
    AND COLUMN_NAME = 'total_winnings');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE daily_operations ADD COLUMN total_winnings DECIMAL(10,2) DEFAULT 0.00 AFTER total_expenses',
    'SELECT "Column total_winnings already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 2: Add status column to users for approval system (v1.0.6)
-- ============================================================================

SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'status');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN status ENUM("active","inactive","pending") NOT NULL DEFAULT "pending" AFTER passport_photo',
    'SELECT "Column status already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column to winnings table
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'winnings' 
    AND COLUMN_NAME = 'status');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE winnings ADD COLUMN status ENUM("pending","approved","declined") NOT NULL DEFAULT "pending" AFTER ticket_number',
    'SELECT "Column status already exists in winnings" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Add guarantor columns to users (v1.0.8)
-- ============================================================================

SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'guarantor_full_name');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users 
     ADD COLUMN guarantor_full_name VARCHAR(100) DEFAULT NULL AFTER passport_photo,
     ADD COLUMN guarantor_address TEXT DEFAULT NULL AFTER guarantor_full_name,
     ADD COLUMN guarantor_phone VARCHAR(20) DEFAULT NULL AFTER guarantor_address,
     ADD COLUMN guarantor_photo VARCHAR(255) DEFAULT NULL AFTER guarantor_phone',
    'SELECT "Guarantor columns already exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 4: Create staff_shop_assignments table (v1.0.9)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `staff_shop_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `shop_id` (`shop_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `status` (`status`),
  INDEX `idx_staff_shop` (`staff_id`, `shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign keys if they don't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'staff_shop_assignments' 
    AND CONSTRAINT_NAME = 'staff_assignments_staff_fk');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE staff_shop_assignments
     ADD CONSTRAINT staff_assignments_staff_fk FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
     ADD CONSTRAINT staff_assignments_shop_fk FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
     ADD CONSTRAINT staff_assignments_assigned_by_fk FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT "Foreign keys already exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 5: Add shop_code, tips, and other columns to daily_operations (v1.0.9)
-- ============================================================================

SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'daily_operations' 
    AND COLUMN_NAME = 'shop_code');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE daily_operations ADD COLUMN shop_code VARCHAR(20) DEFAULT NULL AFTER shop_id',
    'SELECT "Column shop_code already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'daily_operations' 
    AND COLUMN_NAME = 'tips');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE daily_operations 
     ADD COLUMN tips DECIMAL(10,2) DEFAULT 0.00 AFTER cash_balance,
     ADD COLUMN tips_calculation DECIMAL(10,2) DEFAULT 0.00 AFTER tips,
     ADD COLUMN transfer_to_staff DECIMAL(10,2) DEFAULT 0.00 AFTER closing_balance,
     ADD COLUMN daily_debt DECIMAL(10,2) DEFAULT 0.00 AFTER total_winnings',
    'SELECT "Tips columns already exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Populate shop_code from existing shop_id
UPDATE daily_operations do
INNER JOIN shops s ON do.shop_id = s.id
SET do.shop_code = s.code
WHERE do.shop_code IS NULL;

-- Add unique constraint on daily_operations
SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'daily_operations' 
    AND CONSTRAINT_NAME = 'unique_staff_shop_date');

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE daily_operations ADD UNIQUE KEY unique_staff_shop_date (staff_id, shop_code, operation_date)',
    'SELECT "Unique constraint already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 6: Migrate existing shop assignments (v1.0.9)
-- ============================================================================

INSERT INTO staff_shop_assignments (staff_id, shop_id, assigned_by, assigned_date, status)
SELECT 
    u.id as staff_id,
    u.shop_id,
    1 as assigned_by,
    CURDATE() as assigned_date,
    'active' as status
FROM users u
WHERE u.shop_id IS NOT NULL
AND u.role = 'staff'
AND NOT EXISTS (
    SELECT 1 FROM staff_shop_assignments ssa 
    WHERE ssa.staff_id = u.id AND ssa.shop_id = u.shop_id
)
ON DUPLICATE KEY UPDATE staff_id = staff_id; -- Ignore duplicates

-- ============================================================================
-- PART 7: Fix assignment constraint issue (hotfix v1.0.11)
-- ============================================================================

-- Drop problematic unique constraint if it exists
SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'staff_shop_assignments' 
    AND CONSTRAINT_NAME = 'unique_staff_shop');

SET @sql = IF(@constraint_exists > 0,
    'ALTER TABLE staff_shop_assignments DROP INDEX unique_staff_shop',
    'SELECT "Constraint unique_staff_shop does not exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Clean up duplicate inactive records
DELETE ssa1 FROM staff_shop_assignments ssa1
INNER JOIN staff_shop_assignments ssa2 
WHERE ssa1.staff_id = ssa2.staff_id 
  AND ssa1.shop_id = ssa2.shop_id 
  AND ssa1.status = 'inactive' 
  AND ssa2.status = 'inactive'
  AND ssa1.id < ssa2.id;

-- Ensure index exists
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'staff_shop_assignments' 
    AND INDEX_NAME = 'idx_staff_shop');

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE staff_shop_assignments ADD INDEX idx_staff_shop (staff_id, shop_id)',
    'SELECT "Index idx_staff_shop already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '========================' as '';
SELECT 'UPDATE COMPLETE!' as 'Status';
SELECT '========================' as '';
SELECT 'Verifying schema...' as '';

-- Check key columns exist
SELECT 
    COUNT(*) as 'guarantor_columns_count (should be 4)' 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'darfiden_db' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME LIKE 'guarantor%';

SELECT 
    COUNT(*) as 'daily_ops_new_columns_count (should be 5+)' 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'darfiden_db' 
AND TABLE_NAME = 'daily_operations' 
AND COLUMN_NAME IN ('shop_code', 'tips', 'tips_calculation', 'transfer_to_staff', 'daily_debt');

SELECT 
    COUNT(*) as 'staff_shop_assignments_exists (should be 1)' 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'darfiden_db' 
AND TABLE_NAME = 'staff_shop_assignments';

SELECT '========================' as '';
SELECT 'All updates applied successfully!' as 'Status';
SELECT 'Your database is now at the latest version.' as 'Message';
SELECT '========================' as '';

COMMIT;
