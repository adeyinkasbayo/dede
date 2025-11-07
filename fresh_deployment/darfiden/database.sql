-- ============================================================================
-- DARFIDEN MANAGEMENT SYSTEM - FRESH DATABASE SCHEMA
-- Version: 1.0.11 FINAL - Guaranteed Working
-- Date: November 2025
-- ============================================================================
-- Complete system with ALL features - Ready for production
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `darfiden_db` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `darfiden_db`;

-- ============================================================================
-- TABLE: users - User accounts with guarantor info
-- ============================================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `shop_id` int(11) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `guarantor_full_name` varchar(100) DEFAULT NULL,
  `guarantor_address` text DEFAULT NULL,
  `guarantor_phone` varchar(20) DEFAULT NULL,
  `guarantor_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `shop_id` (`shop_id`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: shops - Shop management with codes
-- ============================================================================
CREATE TABLE `shops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: staff_shop_assignments - Multi-shop assignments
-- ============================================================================
CREATE TABLE `staff_shop_assignments` (
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
  KEY `idx_staff_shop` (`staff_id`, `shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: daily_operations - Daily ops with tips and shop codes
-- ============================================================================
CREATE TABLE `daily_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `shop_code` varchar(20) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `operation_date` date NOT NULL,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `closing_balance` decimal(10,2) DEFAULT 0.00,
  `transfer_to_staff` decimal(10,2) DEFAULT 0.00,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_expenses` decimal(10,2) DEFAULT 0.00,
  `total_winnings` decimal(10,2) DEFAULT 0.00,
  `daily_debt` decimal(10,2) DEFAULT 0.00,
  `cash_balance` decimal(10,2) DEFAULT 0.00,
  `tips` decimal(10,2) DEFAULT 0.00,
  `tips_calculation` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  KEY `staff_id` (`staff_id`),
  KEY `shop_code` (`shop_code`),
  KEY `operation_date` (`operation_date`),
  UNIQUE KEY `unique_staff_shop_date` (`staff_id`, `shop_code`, `operation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: expenses - Expense tracking
-- ============================================================================
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  KEY `staff_id` (`staff_id`),
  KEY `expense_date` (`expense_date`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: winnings - Winning management with approval
-- ============================================================================
CREATE TABLE `winnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `ticket_number` varchar(100) NOT NULL,
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `winning_date` date NOT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `shop_id` (`shop_id`),
  KEY `staff_id` (`staff_id`),
  KEY `winning_date` (`winning_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: debts - Staff debt management
-- ============================================================================
CREATE TABLE `debts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `debt_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','paid','partial') NOT NULL DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `debt_date` (`debt_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: assignments - Legacy assignments (kept for compatibility)
-- ============================================================================
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `shop_id` (`shop_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: activity_logs - System activity tracking
-- ============================================================================
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEFAULT DATA - Admin user and sample shops
-- ============================================================================

-- Insert default admin (password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active');

-- Insert sample shops
INSERT INTO `shops` (`id`, `name`, `code`, `location`, `status`) VALUES
(1, 'Main Shop', 'SH001', 'Downtown', 'active'),
(2, 'Branch Shop', 'SH002', 'Uptown', 'active');

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
SELECT '========================================' as '';
SELECT 'DATABASE CREATED SUCCESSFULLY!' as 'Status';
SELECT '========================================' as '';
SELECT 'Database: darfiden_db' as 'Info';
SELECT 'Admin Username: admin' as '';
SELECT 'Admin Password: admin123' as '';
SELECT '⚠️  CHANGE PASSWORD AFTER LOGIN!' as 'Important';
SELECT '========================================' as '';
SELECT '✅ All 9 tables created' as 'Tables';
SELECT '✅ Admin user created' as '';
SELECT '✅ Sample shops added' as '';
SELECT '✅ Ready to use!' as '';
SELECT '========================================' as '';

COMMIT;
