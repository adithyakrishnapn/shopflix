<?php
require 'vendor/autoload.php';

$env = parse_ini_file('.env');

try {
    $pdo = new PDO(
        'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($env['DB_PORT'] ?? 3306),
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? ''
    );
    echo "✓ MySQL connection successful with user: " . ($env['DB_USERNAME'] ?? 'root') . "\n";
    
    // Try to use the database
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Available databases: " . implode(', ', $databases) . "\n";
    
    if (!in_array($env['DB_DATABASE'] ?? 'testshopflix', $databases)) {
        echo "⚠ Database '" . ($env['DB_DATABASE'] ?? 'testshopflix') . "' does not exist - will be created\n";
    }
    
} catch (PDOException $e) {
    echo "✗ MySQL Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
