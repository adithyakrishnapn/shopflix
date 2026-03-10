<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/install', 'GET');
$response = $kernel->handle($request);
echo "\n--- RESPONSE ---\n";
echo "STATUS: " . $response->getStatusCode() . "\n";
echo "CLASS: " . get_class($response) . "\n";
if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "TARGET: " . $response->getTargetUrl() . "\n";
}
$kernel->terminate($request, $response);
