-- Migration: Add new booking status types
-- This migration adds support for active, ongoing, and returned statuses

-- First, check the current status column definition
-- ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'active', 'ongoing', 'returned', 'completed', 'cancelled') NOT NULL DEFAULT 'pending';

-- Note: The booking status flow should be:
-- 1. pending - Initial booking created, awaiting confirmation
-- 2. confirmed - Booking confirmed by admin
-- 3. active - Start date has arrived, vehicle is ready to be picked up
-- 4. ongoing - Vehicle has been picked up and is currently in use
-- 5. returned - Vehicle has been returned by customer
-- 6. completed - Booking fully processed and closed
-- 7. cancelled - Booking was cancelled

-- Run this SQL manually in your database to update the status column:
ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'active', 'ongoing', 'returned', 'completed', 'cancelled') NOT NULL DEFAULT 'pending';
