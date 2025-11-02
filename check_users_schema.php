<?php
$pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental', 'root', '');
$stmt = $pdo->query('DESCRIBE users');
echo "Users Table Schema:\n";
echo str_repeat('=', 50) . "\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
