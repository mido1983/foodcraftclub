<?php

namespace App\Core\Middleware;

use App\Core\Application;
use App\Core\Exception\ForbiddenException;

class AuthMiddleware extends Middleware {
    protected array $actions = [];
    protected array $roles = [];

    public function __construct(array $actions = [], array $roles = []) {
        $this->actions = $actions;
        $this->roles = $roles;
    }

    public function execute() {
        if (empty($this->actions) || in_array(Application::$app->controller->action, $this->actions)) {
            if (!Application::$app->session->isLoggedIn()) {
                Application::$app->session->setFlash('error', 'Please login to access this page');
                Application::$app->response->redirect('/login');
            }

            if (!empty($this->roles)) {
                $hasRequiredRole = false;
                foreach ($this->roles as $role) {
                    if (Application::$app->session->hasRole($role)) {
                        $hasRequiredRole = true;
                        break;
                    }
                }

                if (!$hasRequiredRole) {
                    throw new ForbiddenException('You do not have permission to access this page');
                }
            }
        }
    }
}
