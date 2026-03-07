<?php
require 'vendor/autoload.php';

// Load the app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$manager = $app->make(\Webkul\Installer\Helpers\EnvironmentManager::class);

$testData = [
    'db_hostname' => '127.0.0.1',
    'db_port' => 3306,
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

echo "Testing environment setup...\n";

try {
    // Create a mock request object
    $request = new Illuminate\Http\Request();
    $request->replace($testData);
    
    $result = $manager->generateEnv($request);
    
    if ($result === true) {
        echo "✓ Environment file setup successful\n";
    } else {
        echo "✗ Environment file setup failed: " . $result . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
