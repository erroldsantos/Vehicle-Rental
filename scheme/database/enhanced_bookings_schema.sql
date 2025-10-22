-- Enhanced Bookings Table for Vehicle Rental System
-- Add new columns and sample data

-- Check current bookings table structure
DESCRIBE bookings;

-- Add additional columns for better booking management
ALTER TABLE bookings 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER deleted_at,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
ADD COLUMN notes TEXT DEFAULT NULL AFTER total_amount;

-- Insert sample bookings for testing
INSERT INTO bookings (booking_reference, user_id, vehicle_id, start_date, end_date, total_amount, status, notes) VALUES
('BK-2025-001', 2, 1, '2025-10-25', '2025-10-28', 300.00, 'confirmed', 'Standard rental booking'),
('BK-2025-002', 4, 2, '2025-10-30', '2025-11-02', 450.00, 'pending', 'Customer requested additional insurance'),
('BK-2025-003', 2, 3, '2025-11-05', '2025-11-07', 200.00, 'confirmed', 'Weekend getaway booking'),
('BK-2025-004', 4, 1, '2025-11-10', '2025-11-15', 750.00, 'cancelled', 'Customer cancelled due to change in plans')
ON DUPLICATE KEY UPDATE booking_reference = VALUES(booking_reference);

-- Update vehicle status based on active bookings
UPDATE vehicles v 
SET status = 'rented' 
WHERE v.id IN (
    SELECT vehicle_id FROM bookings 
    WHERE status = 'confirmed' 
    AND start_date <= CURDATE() 
    AND end_date >= CURDATE()
    AND deleted_at IS NULL
);