-- ============================================
-- Migration: Add Driver's License Verification
-- Description: Add fields for driver's license verification
-- Created: November 18, 2025
-- ============================================

-- Add license verification columns to users table
ALTER TABLE `users` 
ADD COLUMN `license_image` varchar(255) DEFAULT NULL COMMENT 'Path to driver license image',
ADD COLUMN `license_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted' COMMENT 'License verification status',
ADD COLUMN `license_submitted_at` datetime DEFAULT NULL COMMENT 'When license was submitted',
ADD COLUMN `license_verified_at` datetime DEFAULT NULL COMMENT 'When license was verified',
ADD COLUMN `license_verified_by` int(11) DEFAULT NULL COMMENT 'Admin who verified the license',
ADD COLUMN `license_rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection if rejected';

-- Add foreign key for verified_by admin
ALTER TABLE `users`
ADD CONSTRAINT `fk_license_verified_by` 
FOREIGN KEY (`license_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add index for faster queries
ALTER TABLE `users`
ADD INDEX `idx_license_status` (`license_status`);
