<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Initialize the application
$app = new Application(dirname(__DIR__));

try {
    // Connect to database
    $pdo = $app->db->pdo;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if notes column already exists
    $checkNotesColumn = $pdo->query("SHOW COLUMNS FROM orders LIKE 'notes'");
    if ($checkNotesColumn->rowCount() === 0) {
        // Add notes field to orders table
        $pdo->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER delivery_fee");
        echo "[+] Successfully added notes column to orders table\n";
    } else {
        echo "[*] Notes column already exists in orders table\n";
    }
    
    // Check if order_metadata table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'order_metadata'");
    if ($checkTable->rowCount() === 0) {
        // Create order_metadata table
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_metadata (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            order_id BIGINT NOT NULL,
            phone VARCHAR(50) NULL,
            delivery_notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Successfully created order_metadata table\n";
    } else {
        echo "[*] order_metadata table already exists\n";
    }
    
    // Add migration record
    $checkMigration = $pdo->prepare("SELECT * FROM migrations WHERE migration = :migration");
    $checkMigration->execute(['migration' => 'm0004_add_order_notes_and_metadata.php']);
    if ($checkMigration->rowCount() === 0) {
        $insertMigration = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $insertMigration->execute(['migration' => 'm0004_add_order_notes_and_metadata.php']);
        echo "[+] Added migration record\n";
    } else {
        echo "[*] Migration record already exists\n";
    }
    
    // Commit transaction
    $pdo->commit();
    echo "[+] All changes committed successfully\n";
    
} catch (\Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[!] Error: " . $e->getMessage() . "\n";
}
