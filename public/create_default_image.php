<?php
// Скрипт для создания дефолтного изображения продукта

// Создаем изображение 800x600
$image = imagecreatetruecolor(800, 600);

// Цвета
$background = imagecolorallocate($image, 245, 245, 245); // Светло-серый фон
$text_color = imagecolorallocate($image, 100, 100, 100); // Темно-серый текст
$border = imagecolorallocate($image, 200, 200, 200); // Серая рамка

// Заполняем фон
imagefilledrectangle($image, 0, 0, 800, 600, $background);

// Рисуем рамку
imagerectangle($image, 10, 10, 790, 590, $border);

// Добавляем текст
$font_size = 20;
$text = 'Изображение продукта отсутствует';

// Центрируем текст
$text_box = imagettfbbox($font_size, 0, 'arial', $text);
$text_width = $text_box[2] - $text_box[0];
$text_height = $text_box[7] - $text_box[1];
$x = (800 - $text_width) / 2;
$y = (600 + $text_height) / 2;

// Если шрифт arial недоступен, используем встроенный шрифт
if (!file_exists('arial')) {
    // Используем встроенный шрифт
    $font = 5; // Размер встроенного шрифта (1-5)
    // Получаем размеры текста с встроенным шрифтом
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    $x = (800 - $text_width) / 2;
    $y = (600 + $text_height) / 2;
    
    // Рисуем текст встроенным шрифтом
    imagestring($image, $font, $x, $y - $text_height, $text, $text_color);
} else {
    // Рисуем текст с использованием TrueType шрифта
    imagettftext($image, $font_size, 0, $x, $y, $text_color, 'arial', $text);
}

// Рисуем иконку
$icon_size = 100;
$icon_x = (800 - $icon_size) / 2;
$icon_y = (600 - $icon_size) / 2 - 50;

// Рисуем простую иконку (круг с перечеркиванием)
$icon_color = imagecolorallocate($image, 150, 150, 150);
imageellipse($image, 400, 250, $icon_size, $icon_size, $icon_color);
imageline($image, 400 - $icon_size/2, 250 - $icon_size/2, 400 + $icon_size/2, 250 + $icon_size/2, $icon_color);

// Путь для сохранения
$directory = __DIR__ . '/assets/images/';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
}

$file_path = $directory . 'default-product.avif';

// Сохраняем в AVIF если поддерживается, иначе в WEBP
if (function_exists('imageavif')) {
    imageavif($image, $file_path, 75);
    echo "Создано дефолтное изображение в формате AVIF: {$file_path}\n";
} else {
    $file_path = $directory . 'default-product.webp';
    imagewebp($image, $file_path, 75);
    echo "Создано дефолтное изображение в формате WEBP: {$file_path}\n";
}

// Освобождаем память
imagedestroy($image);

echo "Готово!\n";
