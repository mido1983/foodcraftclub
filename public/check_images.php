<?php
// u0421u043au0440u0438u043fu0442 u0434u043bu044f u043fu0440u043eu0432u0435u0440u043au0438 u0438 u0438u0441u043fu0440u0430u0432u043bu0435u043du0438u044f u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Core/Database.php';

// u0418u043du0438u0446u0438u0430u043bu0438u0437u0430u0446u0438u044f u0431u0430u0437u044b u0434u0430u043du043du044bu0445
$config = require_once __DIR__ . '/../config/config.php';
$db = new App\Core\Database($config['db']);

echo "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='UTF-8'>\n<title>u041fu0440u043eu0432u0435u0440u043au0430 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439</title>\n</head>\n<body>\n<h1>u041fu0440u043eu0432u0435u0440u043au0430 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439</h1>";

// u0421u043eu0437u0434u0430u0435u043c u0434u0435u0444u043eu043bu0442u043du043eu0435 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435
if (!file_exists(__DIR__ . '/assets/images/default-products.svg')) {
    $defaultImageContent = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
        <rect width="200" height="200" fill="#f8f9fa"/>
        <text x="50%" y="50%" font-family="Arial" font-size="20" text-anchor="middle" dominant-baseline="middle" fill="#6c757d">No Image</text>
    </svg>';
    
    if (!is_dir(__DIR__ . '/assets/images')) {
        mkdir(__DIR__ . '/assets/images', 0777, true);
    }
    
    file_put_contents(__DIR__ . '/assets/images/default-products.svg', $defaultImageContent);
    echo "<p style='color:green'>u0421u043eu0437u0434u0430u043du043e u0434u0435u0444u043eu043bu0442u043du043eu0435 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435</p>";
}

// u0418u0441u043fu0440u0430u0432u043bu044fu0435u043c u0432u0441u0435 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u044f u043du0430 u0434u0435u0444u043eu043bu0442u043du043eu0435
$statement = $db->prepare("UPDATE products SET image_url = '/assets/images/default-products.svg' WHERE image_url IS NOT NULL AND image_url != ''");
$statement->execute();

echo "<p style='color:green'>u0412u0441u0435 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u043eu0432 u0438u0441u043fu0440u0430u0432u043bu0435u043du044b u043du0430 u0434u0435u0444u043eu043bu0442u043du043eu0435.</p>";

echo "<p><a href='/seller/products'>u0412u0435u0440u043du0443u0442u044cu0441u044f u043a u0441u043fu0438u0441u043au0443 u043fu0440u043eu0434u0443u043au0442u043eu0432</a></p>";

echo "</body>\n</html>";
