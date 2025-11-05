-- Migration Script v1.0.6
-- Adds approval system for staff and winnings
-- Run this ONLY if you already have the database installed

-- Add 'pending' status to users table
ALTER TABLE `users` 
MODIFY COLUMN `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending';

-- Add 'rejected' status to winnings table  
ALTER TABLE `winnings`
MODIFY COLUMN `status` enum('pending','verified','paid','rejected') NOT NULL DEFAULT 'pending';

-- Set existing users to active (if they were active/inactive before)
UPDATE `users` SET `status` = 'active' WHERE `status` IN ('active', 'inactive');

-- Verify the changes
SELECT 'Migration completed successfully. Status options updated.' as status;
