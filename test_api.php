<?php
// Test the installer API endpoint
$apiUrl = "http://127.0.0.1:8000/install/api/env-file-setup";

$postData = [
    'db_hostname' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'testshopflix',
    'db_username' => 'root',
    'db_password' => '',
    'db_connection' => 'mysql',
    'app_name' => 'ShopFlix',
    'app_url' => 'http://127.0.0.1:8000',
    'app_currency' => 'USD',
    'app_locale' => 'en',
    'app_timezone' => 'UTC',
];

echo "Testing API endpoint: $apiUrl\n";
echo "POST data: " . json_encode($postData) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}
?>
