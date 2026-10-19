-- ==========================================================
-- Migration: Add Customer Spinning Wheel, Prizes & Spin Tracking
-- Customer Appreciation Month Raffle Draw System
-- ==========================================================

USE `raffle_db`;

-- 1. Create Prizes Table
CREATE TABLE IF NOT EXISTS `prizes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `segment_label` VARCHAR(60) NOT NULL,
    `probability` DECIMAL(5,2) NOT NULL COMMENT 'Winning probability percentage (e.g. 0.10 for 0.1%)',
    `is_win` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 for prize win, 0 for Try Again',
    `color` VARCHAR(25) NOT NULL DEFAULT '#1a1917',
    `text_color` VARCHAR(25) NOT NULL DEFAULT '#e8d8b0',
    `icon` VARCHAR(50) DEFAULT '',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Populate Configured Prizes
INSERT INTO `prizes` (`id`, `name`, `segment_label`, `probability`, `is_win`, `color`, `text_color`, `icon`, `sort_order`) VALUES
(1, 'iPhone 18 Pro Max', 'iPhone 18 Pro Max', 0.10, 1, '#1c1b18', '#d4af37', '📱', 1),
(2, 'MacBook Neo', 'MacBook Neo', 0.50, 1, '#25221d', '#f3e5ab', '💻', 2),
(3, 'Weekend getaway for two at Marriott Kampala', 'Weekend Getaway', 1.00, 1, '#181b1d', '#e5c158', '🌴', 3),
(4, 'Airfryer', 'Airfryer', 8.00, 1, '#2b261e', '#f0deb4', '🍳', 4),
(5, 'Shopping voucher worth UGX 50,000', 'UGX 50k Voucher', 11.40, 1, '#1b221d', '#e8d8b0', '🎟️', 5),
(6, 'Cornerstone Vacuum Cup', 'Vacuum Cup', 15.00, 1, '#221e25', '#f3e5ab', '☕', 6),
(7, 'Cornerstone Umbrella', 'Umbrella', 15.00, 1, '#1d2228', '#e5c158', '☂️', 7),
(8, 'Cornerstone MagSafe PowerBank', 'PowerBank', 9.00, 1, '#26201b', '#f0deb4', '🔋', 8),
(9, 'Try Again', 'Try Again', 40.00, 0, '#161514', '#a09a8e', '🔄', 9)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `segment_label` = VALUES(`segment_label`),
    `probability` = VALUES(`probability`),
    `is_win` = VALUES(`is_win`),
    `color` = VALUES(`color`),
    `text_color` = VALUES(`text_color`),
    `icon` = VALUES(`icon`),
    `sort_order` = VALUES(`sort_order`);

-- 3. Create Spins Table (Tracks customer attempts)
CREATE TABLE IF NOT EXISTS `spins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `entry_id` INT NOT NULL,
    `spin_number` TINYINT NOT NULL COMMENT '1, 2, or 3',
    `prize_id` INT NOT NULL,
    `is_win` TINYINT(1) NOT NULL DEFAULT 0,
    `spun_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL,
    CONSTRAINT `fk_spins_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spins_entry` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spins_prize` FOREIGN KEY (`prize_id`) REFERENCES `prizes` (`id`),
    CONSTRAINT `uq_customer_spin` UNIQUE (`customer_id`, `spin_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Safely Extend Winners Table with Additional Fields
SET @dbname = DATABASE();
SET @tablename = 'winners';

-- Add customer_id if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'customer_id') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `customer_id` INT NULL AFTER `entry_id`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add prize_id if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'prize_id') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `prize_id` INT NULL AFTER `customer_id`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add claim_code if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'claim_code') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `claim_code` VARCHAR(32) NULL UNIQUE AFTER `prize_id`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add claim_status if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'claim_status') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `claim_status` VARCHAR(20) NOT NULL DEFAULT "unclaimed" AFTER `claim_code`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add email_status if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'email_status') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `email_status` VARCHAR(20) NOT NULL DEFAULT "pending" AFTER `claim_status`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add email_sent_at if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'email_sent_at') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `email_sent_at` TIMESTAMP NULL AFTER `email_status`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add email_error if not exists
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'email_error') > 0,
    'SELECT 1',
    'ALTER TABLE `winners` ADD COLUMN `email_error` TEXT NULL AFTER `email_sent_at`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Backfill customer_id in winners from entries if any exist
UPDATE `winners` w 
INNER JOIN `entries` e ON w.entry_id = e.id 
SET w.customer_id = e.customer_id 
WHERE w.customer_id IS NULL;

-- Backfill existing legacy winners with a default starter prize (e.g., Shopping Voucher) so foreign keys work
UPDATE `winners` SET `prize_id` = 5 WHERE `prize_id` IS NULL;
UPDATE `winners` SET `claim_code` = CONCAT('CLM-LEGACY-', id) WHERE `claim_code` IS NULL;

