-- Hotfix v1.0.11: Fix Duplicate Assignment Constraint Issue
-- Date: 2025
-- Description: Remove problematic unique constraint that causes duplicate entry errors when removing assignments

USE darfiden_db;

-- Check if the table exists
SELECT 'Checking staff_shop_assignments table...' as status;

-- Drop the problematic unique constraint
ALTER TABLE `staff_shop_assignments` DROP INDEX `unique_staff_shop`;

-- Optional: Clean up any duplicate inactive records that may exist
-- This is safe because we're only keeping the most recent inactive record
DELETE ssa1 FROM staff_shop_assignments ssa1
INNER JOIN staff_shop_assignments ssa2 
WHERE ssa1.staff_id = ssa2.staff_id 
  AND ssa1.shop_id = ssa2.shop_id 
  AND ssa1.status = 'inactive' 
  AND ssa2.status = 'inactive'
  AND ssa1.id < ssa2.id;

-- Add index for performance (without unique constraint on status)
ALTER TABLE `staff_shop_assignments` 
ADD INDEX `idx_staff_shop` (`staff_id`, `shop_id`);

-- Show confirmation
SELECT 'Hotfix applied successfully!' as status;
SELECT 'Assignment removal now works without duplicate entry errors.' as note;

COMMIT;

-- Notes:
-- 1. The unique constraint including 'status' was causing issues
-- 2. Records are now deleted instead of marked inactive
-- 3. This prevents duplicate inactive entries
-- 4. Performance index added for lookups
