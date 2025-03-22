<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Models\User;

abstract class DashboardController extends Controller {
    /**
     * Constructor - registers middleware for authentication
     */
    public function __construct() {
        parent::__construct();
        $this->registerMiddleware(new AuthMiddleware());
    }

    /**
     * Get the current user profile
     * @return User|null Current authenticated user
     */
    protected function getUserProfile(): ?User {
        try {
            return Application::$app->session->getUser();
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error getting user profile: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return null;
        }
    }

    /**
     * Get recent notifications for the current user
     * @return array List of notifications
     */
    protected function getNotifications(): array {
        try {
            $user = $this->getUserProfile();
            if (!$user) {
                return [];
            }
            
            $statement = Application::$app->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $statement->execute(['user_id' => $user->id]);
            return $statement->fetchAll();
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error getting notifications: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }

    /**
     * Get count of unread notifications for the current user
     * @return int Count of unread notifications
     */
    protected function getUnreadNotificationsCount(): int {
        try {
            $user = $this->getUserProfile();
            if (!$user) {
                return 0;
            }
            
            $statement = Application::$app->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = 0
            ");
            $statement->execute(['user_id' => $user->id]);
            return (int)($statement->fetch()['count'] ?? 0);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error getting unread notifications count: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return 0;
        }
    }
    
    /**
     * Redirect to another URL
     * @param string $url URL to redirect to
     * @return string Empty string for compatibility with methods that return string
     */
    protected function redirect(string $url): string {
        try {
            Application::$app->response->redirect($url);
            return '';
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error redirecting to ' . $url . ': ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return '';
        }
    }
}
