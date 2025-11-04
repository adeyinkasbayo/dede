-- Migration Script v1.0.3
-- Adds total_winnings and cash_balance fields to daily_operations table
-- Run this ONLY if you already have the database installed

-- Add new columns to daily_operations table
ALTER TABLE `daily_operations` 
ADD COLUMN `total_winnings` decimal(10,2) DEFAULT 0.00 AFTER `total_expenses`,
ADD COLUMN `cash_balance` decimal(10,2) DEFAULT 0.00 AFTER `total_winnings`;

-- Update existing records to calculate cash_balance
-- Formula: Cash Balance = Opening Balance - Closing Balance - Expenses - Winnings
UPDATE `daily_operations` 
SET `cash_balance` = `opening_balance` - `closing_balance` - `total_expenses` - `total_winnings`;

-- Verify the changes
SELECT 'Migration completed successfully. New columns added and calculated.' as status;
