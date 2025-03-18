<?php
// Скрипт для проверки и отображения изображений продуктов
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Core/Application.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Application;
use App\Core\Database;

// Инициализация приложения
$config = require_once __DIR__ . '/../config/config.php';
$app = new Application(dirname(__DIR__), $config);
$db = new Database($config['db']);

echo "<!DOCTYPE html>\n<html lang='ru'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n<title>Проверка отображения изображений</title>\n<style>\nbody { font-family: Arial, sans-serif; margin: 20px; }\n.image-card { border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }\n.image-container { display: flex; align-items: center; }\n.image-preview { margin-right: 20px; width: 150px; height: 150px; object-fit: contain; border: 1px solid #eee; }\n.image-info { flex: 1; }\n.fix-button { background-color: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }\n.fix-button:hover { background-color: #45a049; }\n</style>\n</head>\n<body>\n<h1>Проверка отображения изображений продуктов</h1>";

// Получаем все продукты с изображениями
$products = $db->query("SELECT id, product_name, image_url FROM products WHERE image_url IS NOT NULL AND image_url != ''");

// Функция для проверки существования файла изображения
function checkImageFile($imageUrl) {
    $imagePath = __DIR__ . $imageUrl;
    return file_exists($imagePath);
}

// Функция для исправления изображения
function fixImage($productId, $imageUrl) {
    global $db;
    
    // Парсим URL для получения информации о пользователе и типе
    $path = parse_url($imageUrl, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    if (count($pathParts) >= 3 && $pathParts[0] === 'uploads') {
        $userId = $pathParts[1];
        $type = $pathParts[2];
        
        // Базовая директория для загрузок
        $baseUploadDir = __DIR__ . '/uploads/';
        
        // Путь к директории пользователя
        $userDir = $baseUploadDir . $userId . '/';
        
        // Путь к директории типа файлов
        $typeDir = $userDir . $type . '/';
        
        if (!is_dir($typeDir)) {
            return [false, "Директория не найдена"];
        }
        
        // Получаем список файлов в директории
        $files = scandir($typeDir);
        $oldFileName = basename($imageUrl); // Получаем только имя файла без пути
        
        // Ищем файл по частичному совпадению
        $foundFile = null;
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            // Проверяем, если файл содержит часть имени или совпадает полностью
            if (strpos($file, $oldFileName) !== false || strpos($oldFileName, $file) !== false) {
                $foundFile = $file;
                break;
            }
        }
        
        if ($foundFile) {
            // Создаем новое корректное имя файла
            $fileInfo = pathinfo($foundFile);
            $extension = strtolower($fileInfo['extension']);
            
            // Проверяем расширение
            if (!in_array($extension, ['avif', 'webp', 'png', 'jpg', 'jpeg'])) {
                $extension = 'webp'; // По умолчанию используем webp
            }
            
            // Создаем новое имя файла
            $newFileName = $type . '_' . time() . '_' . md5($foundFile . time()) . '.' . $extension;
            $oldPath = $typeDir . $foundFile;
            $newPath = $typeDir . $newFileName;
            
            // Переименовываем файл
            if (rename($oldPath, $newPath)) {
                $newImageUrl = '/uploads/' . $userId . '/' . $type . '/' . $newFileName;
                
                // Обновляем запись в базе данных
                $db->query("UPDATE products SET image_url = :image_url WHERE id = :id", [
                    'image_url' => $newImageUrl,
                    'id' => $productId
                ]);
                
                return [true, $newImageUrl];
            } else {
                return [false, "Ошибка переименования файла"];
            }
        } else {
            return [false, "Файл не найден в директории"];
        }
    }
    
    return [false, "Неверный формат пути"];
}

// Обработка запроса на исправление
if (isset($_POST['fix_image']) && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $imageUrl = $_POST['image_url'];
    
    list($success, $message) = fixImage($productId, $imageUrl);
    
    if ($success) {
        echo "<div style='background-color: #dff0d8; color: #3c763d; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
        echo "Изображение для продукта #{$productId} успешно исправлено. Новый URL: {$message}";
        echo "</div>";
    } else {
        echo "<div style='background-color: #f2dede; color: #a94442; padding: 15px; margin-bottom: 20px; border-radius: 4px;'>";
        echo "Ошибка при исправлении изображения для продукта #{$productId}: {$message}";
        echo "</div>";
    }
    
    // Получаем обновленный список продуктов
    $products = $db->query("SELECT id, product_name, image_url FROM products WHERE image_url IS NOT NULL AND image_url != ''");
}

// Отображаем все продукты с изображениями
foreach ($products as $product) {
    $imageExists = checkImageFile($product['image_url']);
    $statusClass = $imageExists ? 'success' : 'danger';
    $statusText = $imageExists ? 'Изображение существует' : 'Изображение не найдено';
    
    echo "<div class='image-card'>";
    echo "<h3>{$product['product_name']} (ID: {$product['id']})</h3>";
    echo "<div class='image-container'>";
    
    // Отображаем изображение или дефолтное изображение
    if ($imageExists) {
        echo "<img src='{$product['image_url']}' alt='{$product['product_name']}' class='image-preview'>";
    } else {
        echo "<img src='/assets/images/default-products.svg' alt='{$product['product_name']}' class='image-preview'>";
    }
    
    echo "<div class='image-info'>";
    echo "<p><strong>URL изображения:</strong> {$product['image_url']}</p>";
    echo "<p><strong>Статус:</strong> <span style='color: " . ($imageExists ? 'green' : 'red') . "'>{$statusText}</span></p>";
    
    // Полный путь к файлу
    $fullPath = __DIR__ . $product['image_url'];
    echo "<p><strong>Полный путь:</strong> {$fullPath}</p>";
    
    // Если изображение не существует, предлагаем исправить
    if (!$imageExists) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='product_id' value='{$product['id']}'>";
        echo "<input type='hidden' name='image_url' value='{$product['image_url']}'>";
        echo "<button type='submit' name='fix_image' class='fix-button'>Исправить изображение</button>";
        echo "</form>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "<p><a href='/seller/products' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Вернуться к списку продуктов</a></p>";

echo "</body>\n</html>";
