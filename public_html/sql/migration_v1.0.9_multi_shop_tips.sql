-- Migration v1.0.9: Multi-Shop Assignment and Tips Calculation
-- Date: 2025
-- Description: Enable staff to work at multiple shops, add shop_code to daily operations, and add tips calculation

USE darfiden_db;

-- Step 1: Create staff_shop_assignments table for many-to-many relationship
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
  INDEX `idx_staff_shop` (`staff_id`, `shop_id`),
  CONSTRAINT `staff_assignments_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_assignments_shop_fk` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add shop_code and tips columns to daily_operations
ALTER TABLE `daily_operations`
ADD COLUMN `shop_code` VARCHAR(20) DEFAULT NULL AFTER `shop_id`,
ADD COLUMN `tips` DECIMAL(10,2) DEFAULT 0.00 AFTER `cash_balance`,
ADD COLUMN `tips_calculation` DECIMAL(10,2) DEFAULT 0.00 AFTER `tips`,
ADD COLUMN `transfer_to_staff` DECIMAL(10,2) DEFAULT 0.00 AFTER `closing_balance`,
ADD COLUMN `daily_debt` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_winnings`;

-- Step 3: Update existing daily_operations records to populate shop_code from shops table
UPDATE daily_operations do
INNER JOIN shops s ON do.shop_id = s.id
SET do.shop_code = s.code
WHERE do.shop_code IS NULL;

-- Step 4: Migrate existing shop assignments from users table to staff_shop_assignments
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
);

-- Step 5: Add unique constraint to prevent duplicate daily entries per shop code per day
-- First, check if there are any duplicates and handle them
-- (In production, you may need to manually resolve duplicates before adding the constraint)

-- Add the unique constraint
ALTER TABLE `daily_operations`
ADD UNIQUE KEY `unique_staff_shop_date` (`staff_id`, `shop_code`, `operation_date`);

-- Step 6: Update cash_balance formula to include new fields (will be handled by application logic)
-- Formula: Opening Balance + Transfer to Staff - Total Winnings - Total Expenses - Daily Debt - Closing Balance = Cash Balance
-- Tips Calculation: Cash Balance + Tips

COMMIT;

-- Notes:
-- 1. shop_code is now the primary identifier for daily operations
-- 2. staff_shop_assignments allows many-to-many relationship
-- 3. tips_calculation = cash_balance + tips (calculated by application)
-- 4. One entry per staff per shop code per day is enforced by unique constraint
