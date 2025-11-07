-- ============================================================================
-- EMERGENCY DATABASE UPDATE - RUN THIS IMMEDIATELY
-- ============================================================================
-- This fixes the "Column not found: status" errors
-- Run this NOW before using the application
-- ============================================================================

USE darfiden_db;

-- Add status column to users table if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'status');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN status ENUM("active","inactive","pending") NOT NULL DEFAULT "active" AFTER passport_photo',
    'SELECT "Column status already exists in users" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column to shops table if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'darfiden_db' 
    AND TABLE_NAME = 'shops' 
    AND COLUMN_NAME = 'status');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE shops ADD COLUMN status ENUM("active","inactive") NOT NULL DEFAULT "active"',
    'SELECT "Column status already exists in shops" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column to winnings table if it doesn't exist
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

-- Update existing users to active status
UPDATE users SET status = 'active' WHERE status IS NULL OR status = '';

-- Update existing shops to active status  
UPDATE shops SET status = 'active' WHERE status IS NULL OR status = '';

-- Update existing winnings to pending status
UPDATE winnings SET status = 'pending' WHERE status IS NULL OR status = '';

SELECT '========================================' as '';
SELECT 'EMERGENCY UPDATE COMPLETE!' as 'Status';
SELECT '========================================' as '';
SELECT 'Status columns added successfully.' as 'Message';
SELECT 'You can now access the application.' as '';
SELECT '========================================' as '';
SELECT 'NEXT STEP: Run COMPLETE_UPDATE_TO_LATEST.sql' as 'Important';
SELECT 'to get ALL remaining features' as '';
SELECT '========================================' as '';

COMMIT;
