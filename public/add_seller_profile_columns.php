<?php
// Скрипт для добавления недостающих колонок в таблицу seller_profiles

// Подключаем необходимые файлы
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// Получаем конфигурацию базы данных
$dbConfig = require_once dirname(__DIR__) . '/config/db.php';

// Функция для проверки существования колонки в таблице
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

try {
    // Создаем подключение к базе данных
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "<h1>Добавление недостающих колонок в таблицу seller_profiles</h1>";
    
    // Проверяем существование таблицы seller_profiles
    $stmt = $pdo->query("SHOW TABLES LIKE 'seller_profiles'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>Таблица seller_profiles не существует!</p>";
        exit;
    }
    
    // Массив колонок для добавления
    $columnsToAdd = [
        'name' => "ADD COLUMN `name` VARCHAR(255) NULL AFTER `min_order_amount`",
        'description' => "ADD COLUMN `description` TEXT NULL AFTER `name`",
        'email' => "ADD COLUMN `email` VARCHAR(255) NULL AFTER `description`",
        'phone' => "ADD COLUMN `phone` VARCHAR(50) NULL AFTER `email`",
        'avatar_url' => "ADD COLUMN `avatar_url` VARCHAR(255) NULL AFTER `phone`"
    ];
    
    // Проверяем и добавляем каждую колонку
    foreach ($columnsToAdd as $column => $alterStatement) {
        if (columnExists($pdo, 'seller_profiles', $column)) {
            echo "<p style='color: green;'>Колонка '{$column}' уже существует в таблице seller_profiles.</p>";
        } else {
            // Добавляем колонку
            $pdo->exec("ALTER TABLE seller_profiles {$alterStatement}");
            echo "<p style='color: green;'>Колонка '{$column}' успешно добавлена в таблицу seller_profiles.</p>";
        }
    }
    
    // Получаем структуру таблицы после изменений
    $stmt = $pdo->query("DESCRIBE seller_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Структура таблицы seller_profiles:</h2>";
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
    
    // Проверяем существование таблицы seller_payment_options
    $stmt = $pdo->query("SHOW TABLES LIKE 'seller_payment_options'");
    if ($stmt->rowCount() === 0) {
        echo "<h2>Создание таблицы seller_payment_options</h2>";
        // Создаем таблицу seller_payment_options
        $pdo->exec("CREATE TABLE `seller_payment_options` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `seller_profile_id` INT(11) NOT NULL,
            `payment_method_id` INT(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `fk_seller_profile_id` (`seller_profile_id`),
            KEY `fk_payment_method_id` (`payment_method_id`),
            CONSTRAINT `fk_seller_profile_id` FOREIGN KEY (`seller_profile_id`) REFERENCES `seller_profiles` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "<p style='color: green;'>Таблица 'seller_payment_options' успешно создана.</p>";
    } else {
        echo "<h2>Таблица seller_payment_options уже существует</h2>";
    }
    
    echo "<p><a href='/seller/profile'>Вернуться к профилю продавца</a></p>";
    
} catch (PDOException $e) {
    echo "<h1>Ошибка подключения к базе данных</h1>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}
