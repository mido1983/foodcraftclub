<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;

abstract class DashboardController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->registerMiddleware(new AuthMiddleware());
    }

    protected function getUserProfile() {
        return Application::$app->session->getUser();
    }

    protected function getNotifications() {
        $user = $this->getUserProfile();
        $statement = Application::$app->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $statement->execute(['user_id' => $user->id]);
        return $statement->fetchAll();
    }

    protected function getUnreadNotificationsCount() {
        $user = $this->getUserProfile();
        $statement = Application::$app->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = :user_id AND is_read = 0
        ");
        $statement->execute(['user_id' => $user->id]);
        return $statement->fetch()['count'] ?? 0;
    }
}
