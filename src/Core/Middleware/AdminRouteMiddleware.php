<?php

namespace App\Core\Middleware;

use App\Core\Application;
use App\Core\Exception\ForbiddenException;

/**
 * Middleware specifically for protecting admin routes
 * This is applied at the router level before any controller is instantiated
 */
class AdminRouteMiddleware {
    public function __invoke($request, $response) {
        $path = $request->getPath();
        
        // Check if this is an admin route
        if (strpos($path, '/admin') === 0) {
            // Check if user is logged in
            if (!Application::$app->session->isLoggedIn()) {
                Application::$app->session->setFlash('error', 'Please login to access the admin area');
                $response->redirect('/login');
                exit;
            }
            
            // Check if user has admin role
            if (!Application::$app->session->hasRole('admin')) {
                Application::$app->session->setFlash('error', 'You do not have permission to access the admin area');
                $response->redirect('/');
                exit;
            }
        }
    }
}
