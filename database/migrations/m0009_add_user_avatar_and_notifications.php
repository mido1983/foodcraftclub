<?php

class m0009_add_user_avatar_and_notifications {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        // Добавление столбца avatar в таблицу users
        $SQL = "ALTER TABLE users 
            ADD COLUMN avatar VARCHAR(255) NULL,
            ADD COLUMN notification_order TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN notification_promo TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN notification_system TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN phone VARCHAR(50) NULL
        ";
        $db->prepare($SQL)->execute();
        
        // Логирование выполнения миграции
        \App\Core\Application::$app->logger->info(
            'Миграция m0009_add_user_avatar_and_notifications выполнена успешно',
            [],
            'migrations.log'
        );
    }

    public function down() {
        $db = \App\Core\Application::$app->db;
        
        // Удаление столбцов
        $SQL = "ALTER TABLE users 
            DROP COLUMN avatar,
            DROP COLUMN notification_order,
            DROP COLUMN notification_promo,
            DROP COLUMN notification_system,
            DROP COLUMN phone
        ";
        $db->prepare($SQL)->execute();
        
        // Логирование отката миграции
        \App\Core\Application::$app->logger->info(
            'Миграция m0009_add_user_avatar_and_notifications откатана успешно',
            [],
            'migrations.log'
        );
    }
}
