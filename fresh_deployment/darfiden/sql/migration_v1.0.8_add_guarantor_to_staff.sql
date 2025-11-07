-- Migration v1.0.8: Add Guarantor Information to Staff
-- Date: 2025
-- Description: Adds guarantor full name, address, phone, and photo fields to users table

USE darfiden_db;

-- Add guarantor fields to users table
ALTER TABLE `users`
ADD COLUMN `guarantor_full_name` VARCHAR(100) DEFAULT NULL AFTER `passport_photo`,
ADD COLUMN `guarantor_address` TEXT DEFAULT NULL AFTER `guarantor_full_name`,
ADD COLUMN `guarantor_phone` VARCHAR(20) DEFAULT NULL AFTER `guarantor_address`,
ADD COLUMN `guarantor_photo` VARCHAR(255) DEFAULT NULL AFTER `guarantor_phone`;

-- Create guarantor photos directory (to be created manually: /uploads/guarantors/)
-- Ensure proper permissions are set on the uploads/guarantors directory

COMMIT;
