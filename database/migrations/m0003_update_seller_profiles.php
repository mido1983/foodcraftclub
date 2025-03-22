<?php

namespace App\Database\Migrations;

use App\Core\Application;

class m0003_update_seller_profiles {
    public function up() {
        $db = Application::$app->db;
        
        // Добавляем новые поля в таблицу seller_profiles
        $SQL = "ALTER TABLE seller_profiles 
            ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '',
            ADD COLUMN description TEXT,
            ADD COLUMN email VARCHAR(255),
            ADD COLUMN phone VARCHAR(50),
            ADD COLUMN avatar_url VARCHAR(255)";
        
        try {
            $db->prepare($SQL)->execute();
            echo "Migration: Added new fields to seller_profiles table" . PHP_EOL;
            
            // Копируем данные из существующих полей, если они есть
            $updateSQL = "UPDATE seller_profiles 
                SET name = shop_name 
                WHERE shop_name IS NOT NULL AND shop_name != ''";
            $db->prepare($updateSQL)->execute();
            
            $updateDescSQL = "UPDATE seller_profiles 
                SET description = shop_description 
                WHERE shop_description IS NOT NULL AND shop_description != ''";
            $db->prepare($updateDescSQL)->execute();
            
            echo "Migration: Migrated data from old fields to new fields" . PHP_EOL;
            
            return true;
        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
    
    public function down() {
        $db = Application::$app->db;
        
        // Удаляем добавленные поля
        $SQL = "ALTER TABLE seller_profiles 
            DROP COLUMN name,
            DROP COLUMN description,
            DROP COLUMN email,
            DROP COLUMN phone,
            DROP COLUMN avatar_url";
        
        try {
            $db->prepare($SQL)->execute();
            echo "Rollback: Removed fields from seller_profiles table" . PHP_EOL;
            return true;
        } catch (\Exception $e) {
            echo "Rollback failed: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
}
