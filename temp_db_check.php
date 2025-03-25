<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        $_ENV['DB_DSN'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Проверка структуры таблицы users
    $stmt = $pdo->query('DESCRIBE users');
    echo "===== Структура таблицы users =====\n";
    print_r($stmt->fetchAll());
    
    // Проверка, существует ли таблица password_resets
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    $tableExists = $stmt->fetch();
    echo "\n===== Таблица password_resets существует: " . ($tableExists ? 'Да' : 'Нет') . " =====\n";
    
    // Проверка движка таблицы users
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'users'");
    $tableInfo = $stmt->fetch();
    echo "\n===== Информация о таблице users =====\n";
    echo "Engine: " . $tableInfo['Engine'] . "\n";
    echo "Collation: " . $tableInfo['Collation'] . "\n";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
