<?php
/**
 * Update admin user password to hashed version
 * Run this once: php update_admin_password.php
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Hash the password
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin@test.com
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@test.com'");
    $stmt->execute([$hashedPassword]);
    
    echo "✓ Updated admin@test.com password to hashed version\n";
    echo "  Email: admin@test.com\n";
    echo "  Password: admin123\n";
    echo "  Hashed: $hashedPassword\n\n";
    
    // Also update other users if they exist
    $users = [
        ['email' => 'john.doe@email.com', 'password' => 'password123'],
        ['email' => 'jane.smith@email.com', 'password' => 'password123'],
        ['email' => 'bob.johnson@email.com', 'password' => 'password123'],
    ];
    
    foreach ($users as $user) {
        $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $user['email']]);
        echo "✓ Updated {$user['email']} password\n";
    }
    
    echo "\nAll passwords updated successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
