-- ==========================================================
-- Customer Appreciation Month Raffle Draw System
-- Database Schema (Clean & Beginner-Friendly)
-- ==========================================================

-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS `raffle_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `raffle_db`;

-- 2. Drop existing tables if re-running script (in order of foreign keys)
DROP TABLE IF EXISTS `winners`;
DROP TABLE IF EXISTS `entries`;
DROP TABLE IF EXISTS `customers`;

-- 3. Customers Table
-- Stores basic customer information for participants
CREATE TABLE `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) NOT NULL UNIQUE, -- Phone number is the primary duplicate prevention key
    `email` VARCHAR(120) NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Entries Table
-- Represents individual raffle entries submitted by or assigned to customers
CREATE TABLE `entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `entry_status` VARCHAR(20) NOT NULL DEFAULT 'valid' COMMENT 'valid, won, or disqualified',
    `entry_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraint: Each customer can have at most one raffle entry
    CONSTRAINT `uq_customer_entry` UNIQUE (`customer_id`),
    
    -- Foreign key linking entry to customer
    CONSTRAINT `fk_entries_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Winners Table
-- Records winning entries selected during the raffle draw
CREATE TABLE `winners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT NOT NULL,
    `draw_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraint: An entry can win at most once
    CONSTRAINT `uq_winner_entry` UNIQUE (`entry_id`),
    
    -- Foreign key linking winner to entry
    CONSTRAINT `fk_winners_entry` FOREIGN KEY (`entry_id`) 
        REFERENCES `entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- Sample Starter Data
-- ==========================================================
INSERT INTO `customers` (`name`, `phone`, `email`) VALUES
('Alice Johnson', '555-0101', 'alice.johnson@example.com'),
('Brian Smith', '555-0102', 'brian.smith@example.com'),
('Catherine Lee', '555-0103', 'catherine.lee@example.com');

INSERT INTO `entries` (`customer_id`, `entry_status`) VALUES
(1, 'valid'),
(2, 'valid'),
(3, 'valid');

