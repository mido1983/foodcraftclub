<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Models\User;

// Инициализация приложения для доступа к базе данных и логгеру
$config = require_once __DIR__ . '/../config/config.php';
$app = new Application($config);

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 14; // По умолчанию ID 14

try {
    $db = Application::$app->db;
    
    // Проверяем, существует ли пользователь
    $userStmt = $db->prepare("SELECT id, email, full_name FROM users WHERE id = :user_id");
    $userStmt->execute(['user_id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Пользователь с ID {$userId} не найден");
    }
    
    echo "<h2>Информация о пользователе</h2>";
    echo "<p>ID: {$user['id']}</p>";
    echo "<p>Email: {$user['email']}</p>";
    echo "<p>Имя: {$user['full_name']}</p>";
    
    // Проверяем роли пользователя
    $rolesStmt = $db->prepare("
        SELECT r.* FROM roles r
        JOIN user_roles ur ON ur.role_id = r.id
        WHERE ur.user_id = :user_id
    ");
    $rolesStmt->execute(['user_id' => $userId]);
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Роли пользователя</h2>";
    if (empty($roles)) {
        echo "<p>У пользователя нет ролей</p>";
    } else {
        echo "<ul>";
        foreach ($roles as $role) {
            echo "<li>{$role['name']} (ID: {$role['id']})</li>";
        }
        echo "</ul>";
    }
    
    // Проверяем, существует ли уже профиль продавца
    $checkStmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = :user_id");
    $checkStmt->execute(['user_id' => $userId]);
    $existingProfile = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Профиль продавца</h2>";
    if ($existingProfile) {
        echo "<p>Профиль продавца существует:</p>";
        echo "<ul>";
        foreach ($existingProfile as $key => $value) {
            echo "<li>{$key}: {$value}</li>";
        }
        echo "</ul>";
        
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='action' value='delete_profile'>";
        echo "<input type='hidden' name='user_id' value='{$userId}'>";
        echo "<button type='submit'>Удалить профиль продавца</button>";
        echo "</form>";
    } else {
        echo "<p>Профиль продавца не существует</p>";
        
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='action' value='create_profile'>";
        echo "<input type='hidden' name='user_id' value='{$userId}'>";
        echo "<button type='submit'>Создать профиль продавца</button>";
        echo "</form>";
    }
    
    // Обработка действий
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'create_profile') {
            // Создаем профиль продавца
            $createStmt = $db->prepare("
                INSERT INTO seller_profiles (user_id, seller_type, min_order_amount)
                VALUES (:user_id, 'ordinary', 0)
            ");
            $result = $createStmt->execute(['user_id' => $userId]);
            
            if ($result) {
                echo "<p style='color:green;'>Профиль продавца успешно создан!</p>";
                echo "<script>setTimeout(function() { window.location.reload(); }, 1000);</script>";
            } else {
                echo "<p style='color:red;'>Ошибка при создании профиля продавца!</p>";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_profile') {
            // Удаляем профиль продавца
            $deleteStmt = $db->prepare("DELETE FROM seller_profiles WHERE user_id = :user_id");
            $result = $deleteStmt->execute(['user_id' => $userId]);
            
            if ($result) {
                echo "<p style='color:green;'>Профиль продавца успешно удален!</p>";
                echo "<script>setTimeout(function() { window.location.reload(); }, 1000);</script>";
            } else {
                echo "<p style='color:red;'>Ошибка при удалении профиля продавца!</p>";
            }
        }
    }
    
    echo "<h2>Действия</h2>";
    echo "<p><a href='/seller/products/new'>Перейти к добавлению товара</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Ошибка</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
}
