<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Models\User;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Initialize application to get database connection
$app = new Application(dirname(__DIR__));

try {
    // Create database if not exists
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$_ENV['DB_DATABASE']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";

    // Connect to the database
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Run migration
    $sql = file_get_contents(__DIR__ . '/migrations/001_create_initial_tables.sql');
    $pdo->exec($sql);
    echo "Migration completed successfully\n";

    // Create admin user
    $user = new User();
    $user->email = 'michael.doroshenko1@gmail.com';
    $user->full_name = 'Michael Dor';
    $user->setPassword('Admin123!'); // We'll change this after first login
    $user->status = 'active';

    if ($user->save()) {
        $user->setRoles([1]); // Set as admin (role_id = 1)
        echo "Admin user created successfully\n";
        echo "Email: michael.doroshenko1@gmail.com\n";
        echo "Password: Admin123!\n";
        echo "Please change the password after first login\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
