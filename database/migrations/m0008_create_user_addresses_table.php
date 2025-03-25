<?php

class m0008_create_user_addresses_table {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        // u0421u043eu0437u0434u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b u0430u0434u0440u0435u0441u043eu0432 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439
        $SQL = "CREATE TABLE IF NOT EXISTS user_addresses (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            address_type ENUM('billing','shipping') NOT NULL DEFAULT 'shipping',
            is_default BOOLEAN NOT NULL DEFAULT FALSE,
            full_name VARCHAR(255) NOT NULL,
            address_line1 VARCHAR(255) NOT NULL,
            address_line2 VARCHAR(255),
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100),
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) NOT NULL,
            phone VARCHAR(50),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();
        
        // u041bu043eu0433u0438u0440u043eu0432u0430u043du0438u0435 u0441u043eu0437u0434u0430u043du0438u044f u0442u0430u0431u043bu0438u0446u044b
        \App\Core\Application::$app->logger->info("Migration: Created user_addresses table", [], 'migrations.log');
    }

    public function down() {
        $db = \App\Core\Application::$app->db;
        
        // u0423u0434u0430u043bu0435u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b u0430u0434u0440u0435u0441u043eu0432 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439
        $SQL = "DROP TABLE IF EXISTS user_addresses;";
        $db->prepare($SQL)->execute();
        
        // u041bu043eu0433u0438u0440u043eu0432u0430u043du0438u0435 u0443u0434u0430u043bu0435u043du0438u044f u0442u0430u0431u043bu0438u0446u044b
        \App\Core\Application::$app->logger->info("Migration: Dropped user_addresses table", [], 'migrations.log');
    }
}
