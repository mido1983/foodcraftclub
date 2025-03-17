<?php

use App\Controllers\SiteController;
use App\Controllers\AuthController;
use App\Controllers\SellerDashboardController;

/** @var \App\Core\Router $router */

// Site routes
$router->get('/', [SiteController::class, 'home']);
$router->get('/404', [SiteController::class, 'error']);

// Authentication routes
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Admin routes (protected by middleware)
$router->get('/admin/users/create', [AuthController::class, 'register']);
$router->post('/admin/users/create', [AuthController::class, 'register']);

// Seller routes
$router->get('/seller/dashboard', [SellerDashboardController::class, 'index']);
$router->get('/seller/products', [SellerDashboardController::class, 'products']);
$router->get('/seller/orders', [SellerDashboardController::class, 'orders']);
$router->get('/seller/profile', [SellerDashboardController::class, 'profile']);
$router->get('/seller/notifications', [SellerDashboardController::class, 'notifications']);
