<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(__DIR__);

try {
    // Connect to database
    $pdo = $app->db->pdo;
    
    echo "\n===== ТАБЛИЦЫ В БАЗЕ ДАННЫХ =====\n";
    $tables = $pdo->query("SHOW TABLES");
    while ($table = $tables->fetch(PDO::FETCH_NUM)) {
        echo "- {$table[0]}\n";
    }
    
    echo "\n===== СТРУКТУРА ТАБЛИЦЫ ORDERS =====\n";
    $orderColumns = $pdo->query("SHOW COLUMNS FROM orders");
    while ($column = $orderColumns->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n===== ПРОВЕРКА ТАБЛИЦЫ ORDER_METADATA =====\n";
    $metadataExists = $pdo->query("SHOW TABLES LIKE 'order_metadata'");
    if ($metadataExists->rowCount() > 0) {
        echo "Таблица order_metadata существует!\n";
        
        echo "\n===== СТРУКТУРА ТАБЛИЦЫ ORDER_METADATA =====\n";
        $metadataColumns = $pdo->query("SHOW COLUMNS FROM order_metadata");
        while ($column = $metadataColumns->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "Таблица order_metadata НЕ существует!\n";
        
        echo "\n===== СОЗДАНИЕ ТАБЛИЦЫ ORDER_METADATA =====\n";
        $createMetadata = $pdo->exec("CREATE TABLE IF NOT EXISTS order_metadata (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            order_id BIGINT NOT NULL,
            phone VARCHAR(50) NULL,
            delivery_notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        if ($createMetadata !== false) {
            echo "Таблица order_metadata успешно создана!\n";
        } else {
            echo "Ошибка при создании таблицы order_metadata!\n";
        }
    }
    
    echo "\n===== ПРОВЕРКА ПОЛЯ NOTES В ТАБЛИЦЕ ORDERS =====\n";
    $notesExists = $pdo->query("SHOW COLUMNS FROM orders LIKE 'notes'");
    if ($notesExists->rowCount() > 0) {
        echo "Поле notes существует в таблице orders!\n";
    } else {
        echo "Поле notes НЕ существует в таблице orders!\n";
        
        echo "\n===== ДОБАВЛЕНИЕ ПОЛЯ NOTES В ТАБЛИЦУ ORDERS =====\n";
        $addNotes = $pdo->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER delivery_fee");
        
        if ($addNotes !== false) {
            echo "Поле notes успешно добавлено в таблицу orders!\n";
        } else {
            echo "Ошибка при добавлении поля notes в таблицу orders!\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
