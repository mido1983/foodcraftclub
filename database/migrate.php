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

// Run migrations
try {
    $app->db->applyMigrations();
} catch (Exception $e) {
    die("Migration Error: " . $e->getMessage());
}
