<?php

class m0004_add_order_notes_and_metadata {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        try {
            // Add notes field to orders table
            $db->pdo->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER delivery_fee");
            echo "[+] Successfully added notes column to orders table\n";
            
            // Create order_metadata table
            $db->pdo->exec("CREATE TABLE IF NOT EXISTS order_metadata (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                order_id BIGINT NOT NULL,
                phone VARCHAR(50) NULL,
                delivery_notes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            echo "[+] Successfully created order_metadata table\n";
        } catch (\Exception $e) {
            echo "[!] Error: " . $e->getMessage() . "\n";
        }
    }
    
    public function down() {
        $db = \App\Core\Application::$app->db;
        
        try {
            // Drop order_metadata table
            $db->pdo->exec("DROP TABLE IF EXISTS order_metadata");
            echo "[-] Dropped order_metadata table\n";
            
            // Remove notes field from orders table
            $db->pdo->exec("ALTER TABLE orders DROP COLUMN IF EXISTS notes");
            echo "[-] Removed notes column from orders table\n";
        } catch (\Exception $e) {
            echo "[!] Error: " . $e->getMessage() . "\n";
        }
    }
}
