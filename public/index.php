<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Initialize the application
$app = new Application(dirname(__DIR__));

// Load routes
$router = $app->router;
require_once dirname(__DIR__) . '/config/routes.php';

// Start the application
$app->run();
