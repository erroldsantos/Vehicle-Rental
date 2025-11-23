-- ============================================
-- Vehicle Rental System - Complete Database Schema
-- Generated: November 16, 2025
-- Database: vehicle_rental
-- ============================================
-- This schema represents the current production database structure.
-- Use this as the master reference for the database.
-- Safe to run multiple times (uses IF NOT EXISTS).
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Table: users
-- Description: User accounts with email verification
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `license_image` varchar(255) DEFAULT NULL,
  `license_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
  `license_submitted_at` datetime DEFAULT NULL,
  `license_verified_at` datetime DEFAULT NULL,
  `license_verified_by` int(11) DEFAULT NULL,
  `license_rejection_reason` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_verification_token` (`verification_token`),
  KEY `idx_license_status` (`license_status`),
  CONSTRAINT `fk_license_verified_by` FOREIGN KEY (`license_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table: vehicles
-- Description: Vehicle inventory
-- ============================================
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `plate_number` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `status` enum('available','rented','maintenance') DEFAULT 'available',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plate_number` (`plate_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table: bookings
-- Description: Vehicle rental bookings
-- ============================================
CREATE TABLE IF NOT EXISTS `bookings`
-- ============================================
-- Table: bookings
-- Description: Vehicle rental bookings
-- ============================================
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table: maintenance
-- Description: Vehicle maintenance records (including damage reports)
-- ============================================
CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `damage_type` varchar(100) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('scheduled','pending','completed') DEFAULT 'scheduled',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table: payments
-- Description: Payment records for bookings
-- ============================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_type` enum('full','downpayment') DEFAULT 'full',
  `status` enum('pending','completed') DEFAULT 'pending',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Default Data
-- ============================================

-- Insert default admin user (if not exists)
-- Password: admin123 (hashed with PASSWORD_BCRYPT)
INSERT IGNORE INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `email_verified`) 
VALUES (1, 'Admin', 'User', 'admin@vehiclerental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

