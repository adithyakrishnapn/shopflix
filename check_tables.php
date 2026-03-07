<?php
require 'vendor/autoload.php';

$env = parse_ini_file('.env');

try {
    $pdo = new PDO(
        'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($env['DB_DATABASE'] ?? 'testshopflix'),
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? ''
    );
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n";
    if (count($tables) > 0) {
        echo "Tables: " . implode(', ', array_slice($tables, 0, 10)) . (count($tables) > 10 ? '...' : '') . "\n";
    } else {
        echo "No tables found - database needs migration\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
}
?>
