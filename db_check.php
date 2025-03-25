<?php

try {
    $db = new PDO('mysql:host=localhost;dbname=foodcraftclub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получение структуры таблицы product_ingredients
    $stmt = $db->query('SHOW COLUMNS FROM product_ingredients');
    echo "Структура таблицы product_ingredients:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    // Получение первых 5 записей из таблицы base_ingredients
    $stmt = $db->query('SELECT * FROM base_ingredients LIMIT 5');
    echo "\nПримеры записей из таблицы base_ingredients:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
