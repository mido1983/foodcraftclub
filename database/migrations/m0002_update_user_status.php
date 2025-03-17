<?php

use App\Core\Application;

class m0002_update_user_status {
    public function up() {
        $db = Application::$app->db;
        
        // Обновляем поле status в таблице users
        $SQL = "ALTER TABLE users MODIFY COLUMN status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active';";
        
        try {
            $db->beginTransaction();
            
            // Выполняем SQL запрос
            $stmt = $db->prepare($SQL);
            $result = $stmt->execute();
            
            if ($result) {
                echo "[+] Успешно обновлено поле status в таблице users\n";
                
                // Проверяем и обновляем существующие записи с неправильными статусами
                $checkStmt = $db->prepare("SELECT id, status FROM users WHERE status NOT IN ('active', 'pending', 'suspended')");
                $checkStmt->execute();
                $invalidUsers = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($invalidUsers)) {
                    echo "[*] Найдены пользователи с недопустимыми статусами: " . count($invalidUsers) . "\n";
                    
                    // Обновляем недопустимые статусы на 'active'
                    $updateStmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = :id");
                    
                    foreach ($invalidUsers as $user) {
                        echo "    - Пользователь ID: {$user['id']}, статус: {$user['status']} -> 'active'\n";
                        $updateStmt->execute(['id' => $user['id']]);
                    }
                    
                    echo "[+] Все недопустимые статусы обновлены на 'active'\n";
                } else {
                    echo "[*] Недопустимых статусов не найдено\n";
                }
            } else {
                echo "[-] Ошибка при обновлении поля status в таблице users\n";
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            echo "[-] Ошибка миграции: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function down() {
        $db = Application::$app->db;
        
        // Возвращаем предыдущее определение поля status
        $SQL = "ALTER TABLE users MODIFY COLUMN status ENUM('active','banned') NOT NULL DEFAULT 'active';";
        
        try {
            $db->beginTransaction();
            
            // Сначала обновляем все записи с 'pending' или 'suspended' на 'active'
            $updateStmt = $db->prepare("UPDATE users SET status = 'active' WHERE status IN ('pending', 'suspended')");
            $updateStmt->execute();
            $updatedCount = $updateStmt->rowCount();
            echo "[*] Обновлено {$updatedCount} пользователей со статусами 'pending' или 'suspended' на 'active'\n";
            
            // Выполняем SQL запрос для изменения структуры
            $stmt = $db->prepare($SQL);
            $result = $stmt->execute();
            
            if ($result) {
                echo "[+] Успешно восстановлено предыдущее определение поля status в таблице users\n";
            } else {
                echo "[-] Ошибка при восстановлении поля status в таблице users\n";
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            echo "[-] Ошибка отката миграции: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
