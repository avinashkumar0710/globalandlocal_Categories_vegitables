<?php
// Simple test to check if public API endpoint responds

// Use cURL to test the endpoint
$url = 'http://localhost/TABLE/public_api.php?action=get_visitor_data';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
if ($error) {
    echo "Error: " . $error . "\n";
}
echo "Response: " . $response . "\n";
?>