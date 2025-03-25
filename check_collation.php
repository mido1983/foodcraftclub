<?php

try {
    $db = new PDO('mysql:host=localhost;dbname=foodcraftclub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем кодировку таблицы product_ingredients
    $stmt = $db->query('SHOW TABLE STATUS LIKE "product_ingredients"');
    $tableStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Структура таблицы product_ingredients:\n";
    echo "Кодировка: " . $tableStatus['Collation'] . "\n";
    
    // Проверяем кодировку таблицы base_ingredients
    $stmt = $db->query('SHOW TABLE STATUS LIKE "base_ingredients"');
    $tableStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nСтруктура таблицы base_ingredients:\n";
    echo "Кодировка: " . $tableStatus['Collation'] . "\n";
    
    // Проверяем кодировку столбца ingredient_name в product_ingredients
    $stmt = $db->query('SHOW FULL COLUMNS FROM product_ingredients WHERE Field = "ingredient_name"');
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nИнформация о столбце ingredient_name:\n";
    echo "Кодировка: " . $columnInfo['Collation'] . "\n";
    
    // Проверяем кодировку столбца name в base_ingredients
    $stmt = $db->query('SHOW FULL COLUMNS FROM base_ingredients WHERE Field = "name"');
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nИнформация о столбце name:\n";
    echo "Кодировка: " . $columnInfo['Collation'] . "\n";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
