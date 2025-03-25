<?php

class m0007_create_preorders_table {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        // Создание таблицы предзаказов
        $SQL = "CREATE TABLE IF NOT EXISTS preorders (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
            notes TEXT,
            expected_date DATE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();
        
        // Логирование создания таблицы
        \App\Core\Application::$app->logger->info("Migration: Created preorders table", [], 'migrations.log');
    }

    public function down() {
        $db = \App\Core\Application::$app->db;
        
        // Удаление таблицы предзаказов
        $SQL = "DROP TABLE IF EXISTS preorders;";
        $db->prepare($SQL)->execute();
        
        // Логирование удаления таблицы
        \App\Core\Application::$app->logger->info("Migration: Dropped preorders table", [], 'migrations.log');
    }
}
