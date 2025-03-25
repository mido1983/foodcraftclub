<?php

class m0006_create_wishlists_table {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        // Создание таблицы избранных товаров
        $SQL = "CREATE TABLE IF NOT EXISTS wishlists (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist_item (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();
        
        // Логирование создания таблицы
        \App\Core\Application::$app->logger->info("Migration: Created wishlists table", [], 'migrations.log');
    }

    public function down() {
        $db = \App\Core\Application::$app->db;
        
        // Удаление таблицы избранных товаров
        $SQL = "DROP TABLE IF EXISTS wishlists;";
        $db->prepare($SQL)->execute();
        
        // Логирование удаления таблицы
        \App\Core\Application::$app->logger->info("Migration: Dropped wishlists table", [], 'migrations.log');
    }
}
