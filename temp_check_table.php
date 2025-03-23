<?php
require_once __DIR__ . '/src/Core/Application.php';

// Инициализируем приложение
$app = \App\Core\Application::$app;

try {
    // Проверяем структуру таблицы seller_payment_options
    $db = $app->db;
    $stmt = $db->prepare("DESCRIBE seller_payment_options");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Структура таблицы seller_payment_options:\n";
    print_r($columns);
    
    // Проверяем содержимое таблицы
    $stmt = $db->prepare("SELECT * FROM seller_payment_options");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nСодержимое таблицы seller_payment_options:\n";
    print_r($rows);
    
    // Проверяем доступные методы оплаты
    $stmt = $db->prepare("SELECT * FROM payment_methods");
    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nДоступные методы оплаты:\n";
    print_r($methods);
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
