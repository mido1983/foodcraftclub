<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Request;
use App\Models\User;
use Exception;
use PDO;

class SellerDashboardController extends DashboardController {
    protected Request $request;
    
    public function __construct() {
        parent::__construct();
        $this->registerMiddleware(new AuthMiddleware([], ['seller']));
        $this->request = Application::$app->request;
    }

    public function index() {
        $this->view->title = 'Seller Dashboard';
        
        Application::$app->logger->info(
            'Request to seller page', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->logger->warning(
                    'Attempt to access seller page without authorization', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            $roles = $user->getRoles();
            $hasSellerRole = false;
            foreach ($roles as $role) {
                if ($role['name'] === 'seller') {
                    $hasSellerRole = true;
                    break;
                }
            }
            
            if (!$hasSellerRole) {
                Application::$app->logger->warning(
                    'Attempt to access seller page without seller role', 
                    ['user_id' => $user->id, 'roles' => $roles],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You do not have permission to access this page');
                Application::$app->response->redirect('/');
                return '';
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Seller profile not found when accessing the page', 
                    ['user_id' => $user->id],
                    'users.log'
                );
            }
            
            Application::$app->logger->info(
                'Successful access to seller page', 
                ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile ? $sellerProfile['id'] : null],
                'users.log'
            );
            
            return $this->render('seller/dashboard/index', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'stats' => $this->getSellerStats($user->id),
                'recentOrders' => $this->getRecentOrders($user->id),
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error accessing seller page: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while loading the seller page. Please try again.');
            
            Application::$app->response->redirect('/');
            return '';
        }
    }

    public function products() {
        $this->view->title = 'My Products';
        
        Application::$app->logger->info(
            'Request to products page', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->logger->warning(
                    'Attempt to access products page without authorization', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            Application::$app->logger->info(
                'User accessed products page', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'users.log'
            );
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Attempt to access products page without seller profile', 
                    ['user_id' => $user->id],
                    'errors.log'
                );
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            $checkCategoriesTable = Application::$app->db->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'categories'
            ");
            $checkCategoriesTable->execute();
            $categoriesTableExists = $checkCategoriesTable->fetchColumn();
            
            if ($categoriesTableExists) {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            } else {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, '' as category_name
                    FROM products p
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            }
            
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            $products = $statement->fetchAll();
            
            Application::$app->logger->info(
                'Products found', 
                ['user_id' => $user->id, 'count' => count($products)],
                'users.log'
            );
            
            $categories = [];
            if ($categoriesTableExists) {
                $categoriesStmt = Application::$app->db->prepare("SELECT id, name FROM categories ORDER BY name");
                $categoriesStmt->execute();
                $categories = $categoriesStmt->fetchAll();
            }
            
            return $this->render('seller/dashboard/products', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'products' => $products,
                'categories' => $categories,
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error accessing products page: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while loading the products page. Please try again.');
            
            Application::$app->response->redirect('/seller');
            return '';
        }
    }

    protected function getSellerProfile(int $userId): ?array {
        try {
            $statement = Application::$app->db->prepare("
                SELECT * FROM seller_profiles
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $statement->execute(['user_id' => $userId]);
            $profile = $statement->fetch(PDO::FETCH_ASSOC);
            
            return $profile ?: null;
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting seller profile: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return null;
        }
    }

    protected function getSellerStats(int $userId): array {
        try {
            $sellerProfile = $this->getSellerProfile($userId);
            
            if (!$sellerProfile) {
                return [
                    'total_products' => 0,
                    'total_orders' => 0,
                    'total_sales' => 0,
                    'avg_rating' => 0
                ];
            }
            
            $productsStmt = Application::$app->db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_profile_id = :seller_profile_id");
            $productsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $totalProducts = $productsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $ordersStmt = Application::$app->db->prepare("
                SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
                FROM orders
                WHERE seller_profile_id = :seller_profile_id
                AND status != 'cancelled'
            ");
            $ordersStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $orderData = $ordersStmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if product_reviews table exists before querying
            $checkTableStmt = Application::$app->db->prepare("SHOW TABLES LIKE 'product_reviews'");
            $checkTableStmt->execute();
            $tableExists = $checkTableStmt->rowCount() > 0;
            
            $avgRating = 0;
            if ($tableExists) {
                $ratingStmt = Application::$app->db->prepare("
                    SELECT AVG(rating) as avg_rating
                    FROM product_reviews
                    WHERE product_id IN (
                        SELECT id FROM products WHERE seller_profile_id = :seller_profile_id
                    )
                ");
                $ratingStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                $avgRating = $ratingStmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0;
            }
            
            return [
                'total_products' => (int)$totalProducts,
                'total_orders' => (int)($orderData['order_count'] ?? 0),
                'total_sales' => (float)($orderData['total_sales'] ?? 0),
                'avg_rating' => round((float)$avgRating, 1)
            ];
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting seller stats: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [
                'total_products' => 0,
                'total_orders' => 0,
                'total_sales' => 0,
                'avg_rating' => 0
            ];
        }
    }

    protected function getRecentOrders(int $userId): array {
        try {
            $sellerProfile = $this->getSellerProfile($userId);
            
            if (!$sellerProfile) {
                return [];
            }
            
            // Modified query to fix the join condition - using customer_id instead of user_id
            $statement = Application::$app->db->prepare("
                SELECT o.*, u.email as customer_email
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.seller_profile_id = :seller_profile_id
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting recent orders: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [];
        }
    }
}
