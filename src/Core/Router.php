<?php

namespace App\Core;

class Router {
    private array $routes = [];
    private array $middlewares = [];

    public function __construct(
        private Request $request,
        private Response $response
    ) {}

    public function get(string $path, $callback) {
        $this->routes['get'][$path] = $callback;
        return $this;
    }

    public function post(string $path, $callback) {
        $this->routes['post'][$path] = $callback;
        return $this;
    }

    public function middleware($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function resolve() {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        if (!$callback) {
            $this->response->setStatusCode(404);
            return $this->renderView('_404');
        }

        // Run middlewares
        foreach ($this->middlewares as $middleware) {
            $middleware($this->request, $this->response);
        }

        if (is_array($callback)) {
            /** @var \App\Core\Controller $controller */
            $controller = new $callback[0]();
            Application::$app->setController($controller);
            $controller->action = $callback[1];
            $callback[0] = $controller;

            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->execute();
            }
        }

        return call_user_func($callback, $this->request, $this->response);
    }

    public function renderView($view, $params = []) {
        return Application::$app->view->renderView($view, $params);
    }

    public function renderContent($viewContent) {
        return Application::$app->view->renderContent($viewContent);
    }
}
