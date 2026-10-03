-- ==========================================================
-- Customer Appreciation Month Raffle Draw System
-- Database Schema & Sample Data
-- ==========================================================

-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS `raffle_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `raffle_db`;

-- 2. Drop existing tables if re-running script (ordered by foreign key dependency)
DROP TABLE IF EXISTS `winners`;
DROP TABLE IF EXISTS `prizes`;
DROP TABLE IF EXISTS `customers`;

-- 3. Customers / Participants Table
CREATE TABLE `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(25) NOT NULL,
    `ticket_number` VARCHAR(30) NOT NULL UNIQUE,
    `is_eligible` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Eligible for draw, 0 = Ineligible/Already Won',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Prizes Table
CREATE TABLE `prizes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `remaining_quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Winners Table
CREATE TABLE `winners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `prize_id` INT NOT NULL,
    `draw_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_winners_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_winners_prize` FOREIGN KEY (`prize_id`) 
        REFERENCES `prizes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- Sample Starter Data (For quick testing & supervisor demo)
-- ==========================================================

-- Sample Customers
INSERT INTO `customers` (`full_name`, `email`, `phone`, `ticket_number`, `is_eligible`) VALUES
('Alice Johnson', 'alice.johnson@example.com', '555-0101', 'TICK-1001', 1),
('Brian Smith', 'brian.smith@example.com', '555-0102', 'TICK-1002', 1),
('Catherine Lee', 'catherine.lee@example.com', '555-0103', 'TICK-1003', 1),
('David Miller', 'david.miller@example.com', '555-0104', 'TICK-1004', 1),
('Elena Davis', 'elena.davis@example.com', '555-0105', 'TICK-1005', 1),
('Frank Wilson', 'frank.wilson@example.com', '555-0106', 'TICK-1006', 1);

-- Sample Prizes
INSERT INTO `prizes` (`name`, `description`, `quantity`, `remaining_quantity`) VALUES
('Grand Prize: 55-inch 4K Smart TV', 'Top-tier 4K Ultra HD smart television with HDR', 1, 1),
('Second Prize: Wireless Noise-Cancelling Headphones', 'Premium over-ear Bluetooth headphones', 2, 2),
('Third Prize: $100 Shopping Voucher', 'Redeemable at participating retail stores', 5, 5);
