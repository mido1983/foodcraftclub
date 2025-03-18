<?php
// Скрипт для проверки структуры таблицы products

// Подключаем необходимые файлы
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// Получаем конфигурацию базы данных
$dbConfig = require_once dirname(__DIR__) . '/config/db.php';

// Создаем подключение к базе данных
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "<h1>Проверка структуры таблицы products</h1>";
    
    // Проверяем существование таблицы products
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>Таблица products не существует!</p>";
        exit;
    }
    
    // Получаем структуру таблицы
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Структура таблицы products:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Проверяем наличие полей is_active и available_for_preorder
    $hasIsActive = false;
    $hasAvailableForPreorder = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_active') {
            $hasIsActive = true;
        }
        if ($column['Field'] === 'available_for_preorder') {
            $hasAvailableForPreorder = true;
        }
    }
    
    echo "<h2>Проверка необходимых полей:</h2>";
    echo "<ul>";
    echo "<li>Поле 'is_active': " . ($hasIsActive ? "<span style='color: green;'>Найдено</span>" : "<span style='color: red;'>Не найдено</span>") . "</li>";
    echo "<li>Поле 'available_for_preorder': " . ($hasAvailableForPreorder ? "<span style='color: green;'>Найдено</span>" : "<span style='color: red;'>Не найдено</span>") . "</li>";
    echo "</ul>";
    
    // Проверяем данные в таблице
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h2>Количество записей в таблице: {$count}</h2>";
    
    if ($count > 0) {
        // Выводим первые 10 записей
        $stmt = $pdo->query("SELECT * FROM products LIMIT 10");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Примеры записей:</h2>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        
        // Заголовки таблицы
        echo "<tr>";
        foreach (array_keys($products[0]) as $header) {
            echo "<th>{$header}</th>";
        }
        echo "</tr>";
        
        // Данные
        foreach ($products as $product) {
            echo "<tr>";
            foreach ($product as $value) {
                echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<h1>Ошибка подключения к базе данных</h1>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}
