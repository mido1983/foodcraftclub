<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;

class SellerDashboardController extends DashboardController {
    public function __construct() {
        parent::__construct();
        $this->registerMiddleware(new AuthMiddleware([], ['seller']));
    }

    public function index() {
        $this->view->title = 'Seller Dashboard';
        
        $user = $this->getUserProfile();
        $sellerProfile = $this->getSellerProfile($user->id);
        
        return $this->render('seller/dashboard/index', [
            'user' => $user,
            'sellerProfile' => $sellerProfile,
            'stats' => $this->getSellerStats($user->id),
            'recentOrders' => $this->getRecentOrders($user->id),
            'notifications' => $this->getNotifications(),
            'unreadNotifications' => $this->getUnreadNotificationsCount()
        ]);
    }

    private function getSellerProfile(int $userId) {
        $statement = Application::$app->db->prepare("
            SELECT * FROM seller_profiles 
            WHERE user_id = :user_id
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch();
    }

    private function getSellerStats(int $userId) {
        // Get total orders, revenue, and active products
        $statement = Application::$app->db->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                (SELECT COUNT(*) FROM products p 
                 WHERE p.seller_profile_id = sp.id AND p.is_active = 1) as active_products
            FROM seller_profiles sp
            LEFT JOIN orders o ON o.seller_profile_id = sp.id
            WHERE sp.user_id = :user_id
            GROUP BY sp.id
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch();
    }

    private function getRecentOrders(int $userId) {
        $statement = Application::$app->db->prepare("
            SELECT o.*, u.full_name as buyer_name
            FROM orders o
            JOIN seller_profiles sp ON o.seller_profile_id = sp.id
            JOIN users u ON o.buyer_id = u.id
            WHERE sp.user_id = :user_id
            ORDER BY o.order_date DESC
            LIMIT 5
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }
}
