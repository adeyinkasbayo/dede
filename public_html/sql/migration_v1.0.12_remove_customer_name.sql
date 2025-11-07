-- ============================================================================
-- Migration: v1.0.12 - Remove customer_name from winnings table
-- Date: 2024
-- Description: Removes the customer_name column from winnings table as it's 
--              not in the latest schema and not being used
-- ============================================================================

USE darfiden_db;

-- Check if customer_name column exists and remove it
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'darfiden_db'
    AND TABLE_NAME = 'winnings'
    AND COLUMN_NAME = 'customer_name'
);

SET @sql = IF(@column_exists > 0,
    'ALTER TABLE winnings DROP COLUMN customer_name',
    'SELECT "Column customer_name does not exist" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the change
SHOW COLUMNS FROM winnings;

-- ============================================================================
-- Migration Complete: v1.0.12
-- ============================================================================
