<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

// Инициализируем приложение
$app = new Application(dirname(__DIR__));

// Тестируем логирование
$app->logger->info('Тестовое сообщение', ['test' => true], 'products.log');

echo "Лог создан. Проверьте файл logs/products.log";
