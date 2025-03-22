<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Database\Database;

// Initialize application to get access to the database and logger
$config = require_once __DIR__ . '/../config/config.php';
new Application($config);

$userId = 14; // ID пользователя, для которого нужно создать профиль продавца

try {
    $db = Application::$app->db;
    
    // Проверяем, существует ли уже профиль продавца
    $checkStmt = $db->prepare("SELECT id FROM seller_profiles WHERE user_id = :user_id");
    $checkStmt->execute(['user_id' => $userId]);
    $existingProfile = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingProfile) {
        // Создаем профиль продавца
        $createStmt = $db->prepare("
            INSERT INTO seller_profiles (user_id, seller_type, min_order_amount)
            VALUES (:user_id, 'ordinary', 0)
        ");
        $result = $createStmt->execute(['user_id' => $userId]);
        
        if ($result) {
            echo "Профиль продавца успешно создан для пользователя ID: {$userId}\n";
            Application::$app->logger->info("Профиль продавца успешно создан для пользователя ID: {$userId}", ['user_id' => $userId], 'users.log');
        } else {
            echo "Ошибка при создании профиля продавца для пользователя ID: {$userId}\n";
            Application::$app->logger->error("Ошибка при создании профиля продавца для пользователя ID: {$userId}", ['user_id' => $userId], 'errors.log');
        }
    } else {
        echo "Профиль продавца уже существует для пользователя ID: {$userId} (Profile ID: {$existingProfile['id']})\n";
        Application::$app->logger->info("Профиль продавца уже существует для пользователя ID: {$userId}", ['user_id' => $userId], 'users.log');
    }
} catch (Exception $e) {
    echo "Ошибка: {$e->getMessage()}\n";
    Application::$app->logger->error("Ошибка при работе с профилем продавца: {$e->getMessage()}", ['user_id' => $userId], 'errors.log');
}
