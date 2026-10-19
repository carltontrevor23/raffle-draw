-- ==========================================================
-- Customer Appreciation Month Raffle Draw System
-- Full Database Schema with Interactive Wheel & Spin Tracking
-- ==========================================================

-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS `raffle_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `raffle_db`;

-- 2. Drop existing tables if re-running script (in order of foreign keys)
DROP TABLE IF EXISTS `spins`;
DROP TABLE IF EXISTS `winners`;
DROP TABLE IF EXISTS `prizes`;
DROP TABLE IF EXISTS `entries`;
DROP TABLE IF EXISTS `customers`;

-- 3. Customers Table
-- Stores customer information for participants
CREATE TABLE `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) NOT NULL UNIQUE, -- Primary duplicate prevention key
    `email` VARCHAR(120) NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Entries Table
-- Represents individual raffle entries assigned to customers
CREATE TABLE `entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `entry_status` VARCHAR(20) NOT NULL DEFAULT 'valid' COMMENT 'valid, won, or disqualified',
    `entry_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uq_customer_entry` UNIQUE (`customer_id`),
    CONSTRAINT `fk_entries_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Prizes Table
-- Configurable raffle prizes with probability distribution
CREATE TABLE `prizes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `segment_label` VARCHAR(60) NOT NULL,
    `probability` DECIMAL(5,2) NOT NULL COMMENT 'Winning probability percentage, sum must equal 100.00%',
    `is_win` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 for prize win, 0 for Try Again',
    `color` VARCHAR(25) NOT NULL DEFAULT '#1a1917',
    `text_color` VARCHAR(25) NOT NULL DEFAULT '#e8d8b0',
    `icon` VARCHAR(50) DEFAULT '',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Winners Table
-- Records winning entries selected during the raffle draw or spinning wheel
CREATE TABLE `winners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT NOT NULL,
    `customer_id` INT NULL,
    `prize_id` INT NULL,
    `claim_code` VARCHAR(32) NULL UNIQUE,
    `claim_status` VARCHAR(20) NOT NULL DEFAULT 'unclaimed',
    `email_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `email_sent_at` TIMESTAMP NULL,
    `email_error` TEXT NULL,
    `draw_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uq_winner_entry` UNIQUE (`entry_id`),
    CONSTRAINT `fk_winners_entry` FOREIGN KEY (`entry_id`) 
        REFERENCES `entries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_winners_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_winners_prize` FOREIGN KEY (`prize_id`) 
        REFERENCES `prizes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Spins Table
-- Records individual spin attempts (max 3 per customer)
CREATE TABLE `spins` (
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

-- ==========================================================
-- Starter Data & Configured Prize Probabilities
-- ==========================================================
INSERT INTO `prizes` (`id`, `name`, `segment_label`, `probability`, `is_win`, `color`, `text_color`, `icon`, `sort_order`) VALUES
(1, 'iPhone 18 Pro Max', 'iPhone 18 Pro Max', 0.10, 1, '#1c1b18', '#d4af37', '📱', 1),
(2, 'MacBook Neo', 'MacBook Neo', 0.50, 1, '#25221d', '#f3e5ab', '💻', 2),
(3, 'Weekend getaway for two at Marriott Kampala', 'Weekend Getaway', 1.00, 1, '#181b1d', '#e5c158', '🌴', 3),
(4, 'Airfryer', 'Airfryer', 8.00, 1, '#2b261e', '#f0deb4', '🍳', 4),
(5, 'Shopping voucher worth UGX 50,000', 'UGX 50k Voucher', 11.40, 1, '#1b221d', '#e8d8b0', '🎟️', 5),
(6, 'Cornerstone Vacuum Cup', 'Vacuum Cup', 15.00, 1, '#221e25', '#f3e5ab', '☕', 6),
(7, 'Cornerstone Umbrella', 'Umbrella', 15.00, 1, '#1d2228', '#e5c158', '☂️', 7),
(8, 'Cornerstone MagSafe PowerBank', 'PowerBank', 9.00, 1, '#26201b', '#f0deb4', '🔋', 8),
(9, 'Try Again', 'Try Again', 40.00, 0, '#161514', '#a09a8e', '🔄', 9);

INSERT INTO `customers` (`id`, `name`, `phone`, `email`) VALUES
(1, 'Alice Johnson', '555-0101', 'alice.johnson@example.com'),
(2, 'Brian Smith', '555-0102', 'brian.smith@example.com'),
(3, 'Catherine Lee', '555-0103', 'catherine.lee@example.com');

INSERT INTO `entries` (`id`, `customer_id`, `entry_status`) VALUES
(1, 1, 'valid'),
(2, 2, 'valid'),
(3, 3, 'valid');
