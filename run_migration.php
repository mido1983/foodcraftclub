<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(__DIR__);

// Check if migration file is provided
if ($argc < 2) {
    die("Usage: php run_migration.php <migration_file>\n");
}

$migrationFile = $argv[1];
$migrationPath = __DIR__ . '/database/migrations/' . $migrationFile;

if (!file_exists($migrationPath)) {
    die("Migration file not found: {$migrationPath}\n");
}

try {
    // Load and run the migration
    require_once $migrationPath;
    $className = pathinfo($migrationFile, PATHINFO_FILENAME);
    $instance = new $className();
    
    echo "Running migration: {$migrationFile}\n";
    $instance->up();
    
    // Save migration to database
    $statement = $app->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
    $statement->execute(['migration' => $migrationFile]);
    
    echo "Migration completed successfully: {$migrationFile}\n";
} catch (Exception $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}
