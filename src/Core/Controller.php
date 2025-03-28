<?php

namespace App\Core;

abstract class Controller {
    public string $action = '';
    protected array $middlewares = [];
    public string $layout = 'main';
    protected ?View $view = null;

    public function __construct() {
        if (isset(Application::$app->view)) {
            $this->view = Application::$app->view;
        } else {
            $this->view = new View();
        }
    }

    public function render($view, $params = []) {
        if ($this->view === null) {
            $this->view = new View();
        }
        return $this->view->renderView($view, $params);
    }

    public function registerMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }

    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    protected function ensureRole(string $role) {
        if (!Application::$app->session->hasRole($role)) {
            Application::$app->response->redirect('/login');
            exit;
        }
    }

    protected function isAjax(): bool {
        return Application::$app->request->isAjax();
    }

    protected function json($data, int $statusCode = 200) {
        return Application::$app->response->json($data, $statusCode);
    }
}
