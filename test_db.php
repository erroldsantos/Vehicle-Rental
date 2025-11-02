<?php
// Simple database connection test
// Access this via: http://localhost/Vehicle-Rental/test_db.php

$host = 'localhost';
$dbname = 'vehicle_rental';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Database Connection Successful!</h2>";
    echo "<p>Database: <strong>$dbname</strong></p>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Check table structure
        echo "<h3>Users Table Structure:</h3>";
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count users
        $count = $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        echo "<h3>Total Users: <strong>$count</strong></h3>";
        
        if ($count > 0) {
            echo "<h3>Sample Users:</h3>";
            $users = $pdo->query("SELECT id, first_name, last_name, email, role, 
                                  COALESCE(phone, 'N/A') as phone, 
                                  COALESCE(status, 'N/A') as status 
                                  FROM users WHERE deleted_at IS NULL LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['first_name']} {$user['last_name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['phone']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>{$user['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ <strong>No users found!</strong> You need to run the SQL scripts to insert sample data.</p>";
            echo "<h3>To Fix:</h3>";
            echo "<ol>";
            echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
            echo "<li>Select the '<strong>vehicle_rental</strong>' database</li>";
            echo "<li>Click 'SQL' tab</li>";
            echo "<li>Run this file: <code>scheme/database/enhanced_users_schema.sql</code></li>";
            echo "</ol>";
        }
    } else {
        echo "<p>❌ Users table does NOT exist</p>";
        echo "<p>You need to create the database schema first!</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<h3>Possible fixes:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check database name is '<strong>vehicle_rental</strong>'</li>";
    echo "<li>Verify credentials in <code>app/config/database.php</code></li>";
    echo "</ul>";
}
?>
