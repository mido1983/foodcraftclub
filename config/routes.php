<?php

use App\Controllers\SiteController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\SellerDashboardController;
use App\Controllers\CatalogController;

/** @var \App\Core\Router $router */

// Site routes
$router->get('/', [SiteController::class, 'home']);
$router->get('/404', [SiteController::class, 'error']);

// Authentication routes
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Admin routes (protected by middleware)
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->get('/admin/users/create', [AuthController::class, 'register']);
$router->post('/admin/users/create', [AuthController::class, 'register']);
$router->get('/admin/users/edit/{id}', [AdminController::class, 'editUser']);
$router->post('/admin/users/edit/{id}', [AdminController::class, 'editUser']);
$router->post('/admin/users/delete/{id}', [AdminController::class, 'deleteUser']);
$router->get('/admin/users/manage-seller-profile/{id}', [AdminController::class, 'manageSellerProfile']);
$router->get('/admin/clear-cache', [AdminController::class, 'clearCache']);

// Catalog routes
$router->get('/catalog', [CatalogController::class, 'index']);
$router->post('/catalog/getProducts', [CatalogController::class, 'getProducts']);

// Debug route (temporary)
$router->get('/debug/logs', function() {
    $errorLogPath = ini_get('error_log');
    echo "<h1>PHP Error Log Location</h1>";
    echo "<p>Error log path: {$errorLogPath}</p>";
    
    if (file_exists($errorLogPath) && is_readable($errorLogPath)) {
        echo "<h2>Last 50 Log Entries</h2>";
        echo "<pre>";
        $logContent = file_get_contents($errorLogPath);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -50);
        echo htmlspecialchars(implode("\n", $lastLines));
        echo "</pre>";
    } else {
        echo "<p>Cannot read error log file. It either doesn't exist or is not readable.</p>";
        
        // Try to find common log locations
        $commonLocations = [
            $_SERVER['DOCUMENT_ROOT'] . '/../logs/php_error.log',
            'C:/xampp/php/logs/php_error.log',
            'C:/xampp/apache/logs/error.log',
            'C:/wamp64/logs/php_error.log'
        ];
        
        echo "<h2>Checking Common Log Locations</h2>";
        echo "<ul>";
        foreach ($commonLocations as $location) {
            if (file_exists($location)) {
                echo "<li>{$location} - EXISTS" . (is_readable($location) ? " (Readable)" : " (Not Readable)") . "</li>";
            } else {
                echo "<li>{$location} - Does not exist</li>";
            }
        }
        echo "</ul>";
    }
});

// Seller routes
$router->get('/seller', [SellerDashboardController::class, 'index']);
$router->get('/seller/dashboard', [SellerDashboardController::class, 'index']);
$router->get('/seller/products', [SellerDashboardController::class, 'products']);
$router->get('/seller/products/fix-images', [SellerDashboardController::class, 'fixProductImages']);
$router->get('/seller/products/new', [SellerDashboardController::class, 'newProduct']); 
$router->post('/seller/products/add', [SellerDashboardController::class, 'addProduct']);
$router->post('/seller/products/edit', [SellerDashboardController::class, 'updateProduct']);
$router->post('/seller/products/delete', [SellerDashboardController::class, 'deleteProduct']);
$router->get('/seller/orders', [SellerDashboardController::class, 'orders']);
$router->get('/seller/profile', [SellerDashboardController::class, 'profile']);
$router->post('/seller/profile', [SellerDashboardController::class, 'profile']);
$router->get('/seller/notifications', [SellerDashboardController::class, 'notifications']);
