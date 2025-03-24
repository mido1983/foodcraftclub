<?php

class m0005_add_product_quantity_and_reservations {
    public function up() {
        $db = \App\Core\Application::$app->db;
        $pdo = $db->pdo;
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if quantity column exists in products table
            $quantityExists = $pdo->query("SHOW COLUMNS FROM products LIKE 'quantity'");
            if ($quantityExists->rowCount() === 0) {
                // Add quantity column to products table
                $pdo->exec("ALTER TABLE products ADD COLUMN quantity INT NOT NULL DEFAULT 0 AFTER price");
                echo "Added quantity column to products table\n";
            }
            
            // Create cart_reservations table
            $pdo->exec("CREATE TABLE IF NOT EXISTS cart_reservations (
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
            echo "Created cart_reservations table\n";
            
            // Commit transaction
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function down() {
        $db = \App\Core\Application::$app->db;
        $pdo = $db->pdo;
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Drop cart_reservations table
            $pdo->exec("DROP TABLE IF EXISTS cart_reservations");
            echo "Dropped cart_reservations table\n";
            
            // Remove quantity column from products table
            $quantityExists = $pdo->query("SHOW COLUMNS FROM products LIKE 'quantity'");
            if ($quantityExists->rowCount() > 0) {
                $pdo->exec("ALTER TABLE products DROP COLUMN quantity");
                echo "Removed quantity column from products table\n";
            }
            
            // Commit transaction
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
