<?php
// Update bookings table schema and add sample data
try {
    $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connecting to database...\n";
    
    // Check if created_at column exists
    $result = $pdo->query('SHOW COLUMNS FROM bookings LIKE "created_at"');
    if ($result->rowCount() == 0) {
        echo "Adding created_at and updated_at columns...\n";
        $pdo->exec('ALTER TABLE bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER deleted_at');
        $pdo->exec('ALTER TABLE bookings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        echo "Timestamp columns added successfully.\n";
    } else {
        echo "Timestamp columns already exist.\n";
    }
    
    // Check if notes column exists
    $result = $pdo->query('SHOW COLUMNS FROM bookings LIKE "notes"');
    if ($result->rowCount() == 0) {
        echo "Adding notes column...\n";
        $pdo->exec('ALTER TABLE bookings ADD COLUMN notes TEXT DEFAULT NULL AFTER total_amount');
        echo "Notes column added successfully.\n";
    } else {
        echo "Notes column already exists.\n";
    }
    
    // Check if bookings table has any data
    $result = $pdo->query('SELECT COUNT(*) FROM bookings');
    $count = $result->fetchColumn();
    
    if ($count == 0) {
        echo "Adding sample bookings...\n";
        $stmt = $pdo->prepare('INSERT INTO bookings (booking_reference, user_id, vehicle_id, start_date, end_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        
        $bookings = [
            ['BK-2025-001', 2, 1, '2025-10-25', '2025-10-28', 300.00, 'confirmed', 'Standard rental booking'],
            ['BK-2025-002', 4, 2, '2025-10-30', '2025-11-02', 450.00, 'pending', 'Customer requested additional insurance'],
            ['BK-2025-003', 2, 3, '2025-11-05', '2025-11-07', 200.00, 'confirmed', 'Weekend getaway booking'],
            ['BK-2025-004', 4, 1, '2025-11-10', '2025-11-15', 750.00, 'cancelled', 'Customer cancelled due to change in plans']
        ];
        
        foreach ($bookings as $booking) {
            $stmt->execute($booking);
        }
        echo "Sample bookings added successfully.\n";
    } else {
        echo "Bookings table already has data ($count bookings).\n";
    }
    
    echo "Bookings schema update completed successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>