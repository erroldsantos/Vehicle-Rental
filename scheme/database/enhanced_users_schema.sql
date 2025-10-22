-- Enhanced Users Table for Vehicle Rental System
-- Add this to extend the existing users table

-- Add new columns to existing users table (if not exists)
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email,
ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER role;

-- Insert sample users for testing
INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES
('Admin', 'User', 'admin@vehiclerental.com', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('John', 'Doe', 'john.doe@email.com', '09987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Jane', 'Smith', 'jane.smith@email.com', '09111111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Mike', 'Johnson', 'mike.johnson@email.com', '09222222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'inactive')
ON DUPLICATE KEY UPDATE email = VALUES(email);