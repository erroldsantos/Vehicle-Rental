<?php
// Password Hash Updater for Vehicle Rental System
// Run this script to convert plain text passwords to hashed passwords

try {
    // Database connection
    $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Get all users with plain text passwords (not starting with $2y$)
    $stmt = $pdo->query("SELECT id, email, password FROM users WHERE password NOT LIKE '$2y$%' AND deleted_at IS NULL");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No plain text passwords found. All passwords are already hashed.\n";
        exit(0);
    }
    
    echo "Found " . count($users) . " users with plain text passwords.\n";
    
    foreach ($users as $user) {
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $user['id']]);
        
        echo "Updated password for user: " . $user['email'] . " (was: " . $user['password'] . ")\n";
    }
    
    echo "\nAll passwords have been successfully hashed!\n";
    echo "You can now login with:\n";
    
    // Show all users
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role FROM users WHERE deleted_at IS NULL ORDER BY role DESC, id ASC");
    $allUsers = $stmt->fetchAll();
    
    foreach ($allUsers as $user) {
        echo "- " . $user['email'] . " (" . $user['first_name'] . " " . $user['last_name'] . " - " . strtoupper($user['role']) . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>