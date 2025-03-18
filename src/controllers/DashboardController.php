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
    
    /**
     * u041fu0435u0440u0435u043du0430u043fu0440u0430u0432u043bu0435u043du0438u0435 u043du0430 u0434u0440u0443u0433u043eu0439 URL
     * @param string $url URL u0434u043bu044f u043fu0435u0440u0435u043du0430u043fu0440u0430u0432u043bu0435u043du0438u044f
     * @return string u041fu0443u0441u0442u0430u044f u0441u0442u0440u043eu043au0430 u0434u043bu044f u0441u043eu0432u043cu0435u0441u0442u0438u043cu043eu0441u0442u0438 u0441 u043cu0435u0442u043eu0434u0430u043cu0438, u043au043eu0442u043eu0440u044bu0435 u0432u043eu0437u0432u0440u0430u0449u0430u044eu0442 u0441u0442u0440u043eu043au0443
     */
    protected function redirect(string $url): string {
        Application::$app->response->redirect($url);
        return '';
    }
}
