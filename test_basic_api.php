<?php
echo "=== Testing Basic API Functionality ===\n";

$url = "http://localhost/Vehicle-Rental/api/bookings/available-vehicles?start_date=2025-11-09&end_date=2025-11-11";

echo "Testing URL: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $http_code\n";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if (isset($data['vehicles'])) {
        echo "SUCCESS: API working, found " . count($data['vehicles']) . " vehicles\n";
        foreach ($data['vehicles'] as $vehicle) {
            echo "- {$vehicle['brand']} {$vehicle['model']} - \${$vehicle['daily_rate']}/day\n";
        }
    } else {
        echo "Response structure issue\n";
        echo substr($response, 0, 200) . "...\n";
    }
} else {
    echo "HTTP Error $http_code\n";
    echo substr($response, 0, 200) . "...\n";
}

echo "\n=== Test Complete ===\n";
?>