<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(__DIR__);

try {
    // Connect to database
    $pdo = $app->db->pdo;
    
    echo "\n===== u0421u0422u0420u0423u041au0422u0423u0420u0410 u0422u0410u0411u041bu0418u0426u042b PRODUCTS =====\n";
    $columns = $pdo->query("SHOW COLUMNS FROM products");
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check if quantity field exists
    $quantityExists = $pdo->query("SHOW COLUMNS FROM products LIKE 'quantity'");
    if ($quantityExists->rowCount() === 0) {
        echo "\nu041fu043eu043bu0435 quantity u041du0415 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 products!\n";
        
        // Add quantity field if it doesn't exist
        echo "\n===== u0414u041eu0411u0410u0412u041bu0415u041du0418u0415 u041fu041eu041bu042f QUANTITY u0412 u0422u0410u0411u041bu0418u0426u0423 PRODUCTS =====\n";
        $addQuantity = $pdo->exec("ALTER TABLE products ADD COLUMN quantity INT NOT NULL DEFAULT 0 AFTER price");
        
        if ($addQuantity !== false) {
            echo "u041fu043eu043bu0435 quantity u0443u0441u043fu0435u0448u043du043e u0434u043eu0431u0430u0432u043bu0435u043du043e u0432 u0442u0430u0431u043bu0438u0446u0443 products!\n";
        } else {
            echo "u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0434u043eu0431u0430u0432u043bu0435u043du0438u0438 u043fu043eu043bu044f quantity u0432 u0442u0430u0431u043bu0438u0446u0443 products!\n";
        }
    } else {
        echo "\nu041fu043eu043bu0435 quantity u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 products!\n";
    }
    
    // Create cart_reservations table if it doesn't exist
    echo "\n===== u041fu0420u041eu0412u0415u0420u041au0410 u0422u0410u0411u041bu0418u0426u042b CART_RESERVATIONS =====\n";
    $reservationsExists = $pdo->query("SHOW TABLES LIKE 'cart_reservations'");
    if ($reservationsExists->rowCount() === 0) {
        echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u041du0415 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442!\n";
        
        echo "\n===== u0421u041eu0417u0414u0410u041du0418u0415 u0422u0410u0411u041bu0418u0426u042b CART_RESERVATIONS =====\n";
        $createReservations = $pdo->exec("CREATE TABLE IF NOT EXISTS cart_reservations (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            reserved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX (expires_at),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        if ($createReservations !== false) {
            echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u0443u0441u043fu0435u0448u043du043e u0441u043eu0437u0434u0430u043du0430!\n";
        } else {
            echo "u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0441u043eu0437u0434u0430u043du0438u0438 u0442u0430u0431u043bu0438u0446u044b cart_reservations!\n";
        }
    } else {
        echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442!\n";
    }
    
} catch (\Exception $e) {
    echo "u041eu0448u0438u0431u043au0430: " . $e->getMessage() . "\n";
}
