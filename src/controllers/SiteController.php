<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;

class SiteController extends Controller {
    /**
     * Render the home page
     * @return string Rendered view
     */
    public function home(): string {
        try {
            $this->view->title = 'Home';
            return $this->render('home', [
                'welcomeMessage' => 'Welcome to Food Craft Club'
            ]);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error rendering home page: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return $this->error();
        }
    }

    /**
     * Render the 404 error page
     * @return string Rendered view
     */
    public function error(): string {
        try {
            Application::$app->response->setStatusCode(404);
            $this->view->title = 'Page Not Found';
            return $this->render('_404');
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error rendering 404 page: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            // Fallback to simple text response if view rendering fails
            return 'Page Not Found (404)';
        }
    }
}
