<?php

echo "Тест PHP работает!\n";

// Проверка подключения к базе данных
try {
    $pdo = new PDO('mysql:host=localhost;dbname=foodcraftclub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Подключение к базе данных успешно!\n";
    
    // Проверка таблицы products
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        echo "Таблица products существует!\n";
        
        // Проверка структуры таблицы products
        $columns = $pdo->query("SHOW COLUMNS FROM products");
        echo "Структура таблицы products:\n";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Проверка наличия поля quantity
        $quantityExists = $pdo->query("SHOW COLUMNS FROM products LIKE 'quantity'");
        if ($quantityExists->rowCount() > 0) {
            echo "\nПоле quantity уже существует в таблице products!\n";
        } else {
            echo "\nПоле quantity НЕ существует в таблице products!\n";
        }
    } else {
        echo "Таблица products НЕ существует!\n";
    }
    
} catch (PDOException $e) {
    echo "Ошибка подключения к базе данных: " . $e->getMessage() . "\n";
}
