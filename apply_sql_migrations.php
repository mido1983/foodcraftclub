<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(__DIR__);

// SQL files to execute
$sqlFiles = [
    'create_wishlists_table.sql',
    'create_preorders_table.sql',
    'create_user_addresses_table.sql'
];

// Execute each SQL file
foreach ($sqlFiles as $sqlFile) {
    $sqlPath = __DIR__ . '/database/migrations/' . $sqlFile;
    
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
        
        // Save migration to database
        $statement = $app->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $statement->execute(['migration' => $sqlFile]);
        echo "Migration recorded in database: {$sqlFile}\n";
        
    } catch (Exception $e) {
        echo "Error executing SQL file {$sqlFile}: " . $e->getMessage() . "\n";
    }
}

echo "\nAll SQL migrations completed.\n";
