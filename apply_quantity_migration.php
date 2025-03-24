<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(__DIR__);

// Include migration file
require_once __DIR__ . '/database/migrations/m0005_add_product_quantity_and_reservations.php';

// Run migration
$migration = new m0005_add_product_quantity_and_reservations();
$result = $migration->up();

if ($result) {
    // Update migrations table
    $pdo = $app->db->pdo;
    $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute(['m0005_add_product_quantity_and_reservations.php']);
    
    echo "\nMigration successfully applied and recorded in the migrations table.\n";
} else {
    echo "\nMigration failed.\n";
}
