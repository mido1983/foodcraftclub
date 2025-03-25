<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Initialize the application
$app = new Application(dirname(__DIR__));

// Create database if not exists
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$_ENV['DB_DATABASE']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}

// SQL files to execute directly
$sqlFiles = [
    'create_wishlists_table.sql',
    'create_preorders_table.sql',
    'create_user_addresses_table.sql'
];

// Execute each SQL file directly
foreach ($sqlFiles as $sqlFile) {
    $sqlPath = __DIR__ . '/migrations/' . $sqlFile;
    
    if (!file_exists($sqlPath)) {
        echo "SQL file not found: {$sqlPath}\n";
        continue;
    }
    
    try {
        // Read SQL content
        $sql = file_get_contents($sqlPath);
        
        // Execute SQL
        echo "Executing SQL file: {$sqlFile}\n";
        $app->db->pdo->exec($sql);
        echo "SQL file executed successfully: {$sqlFile}\n";
        
    } catch (Exception $e) {
        echo "Error executing SQL file {$sqlFile}: " . $e->getMessage() . "\n";
    }
}

// Run PHP migrations
try {
    $app->db->applyMigrations();
} catch (Exception $e) {
    echo "Migration Warning: " . $e->getMessage() . "\n";
    echo "PHP migrations may have partially completed.\n";
}

echo "\nAll migrations completed.\n";
