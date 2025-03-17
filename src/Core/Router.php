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
        
        // Run global middlewares first - BEFORE any route matching
        // This ensures security checks happen before any controller is instantiated
        foreach ($this->middlewares as $middleware) {
            $middleware($this->request, $this->response);
        }
        
        // Check for exact match first
        $callback = $this->routes[$method][$path] ?? false;
        $params = [];
        
        // If no exact match, check for dynamic routes
        if (!$callback) {
            foreach ($this->routes[$method] ?? [] as $route => $handler) {
                $routePattern = $this->convertRouteToRegex($route);
                if (preg_match($routePattern, $path, $matches)) {
                    $callback = $handler;
                    // Extract named parameters
                    $params = $this->extractParams($route, $path);
                    break;
                }
            }
        }

        if (!$callback) {
            $this->response->setStatusCode(404);
            return $this->renderView('_404');
        }

        if (is_array($callback)) {
            /** @var \App\Core\Controller $controller */
            $controller = new $callback[0]();
            Application::$app->setController($controller);
            $controller->action = $callback[1];
            $callback[0] = $controller;

            // Execute controller middlewares BEFORE calling the action
            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->execute();
            }
        }

        // Call the callback with parameters
        return call_user_func_array($callback, array_values($params));
    }
    
    /**
     * Convert a route pattern to a regular expression
     * 
     * @param string $route
     * @return string
     */
    private function convertRouteToRegex(string $route): string {
        // Replace {param} with a regex pattern to capture the value
        $routeRegex = preg_replace('/{([^}]+)}/', '(?P<$1>[^/]+)', $route);
        return "@^{$routeRegex}$@";
    }
    
    /**
     * Extract parameters from a path based on a route pattern
     * 
     * @param string $route
     * @param string $path
     * @return array
     */
    private function extractParams(string $route, string $path): array {
        $params = [];
        
        // Extract parameter names from the route
        preg_match_all('/{([^}]+)}/', $route, $paramNames);
        
        // If no parameters, return empty array
        if (empty($paramNames[1])) {
            return [];
        }
        
        // Convert route to regex and extract values
        $routeRegex = $this->convertRouteToRegex($route);
        preg_match($routeRegex, $path, $matches);
        
        // Build the parameters array
        foreach ($paramNames[1] as $name) {
            $params[$name] = $matches[$name] ?? null;
        }
        
        return $params;
    }

    public function renderView($view, $params = []) {
        return Application::$app->view->renderView($view, $params);
    }

    public function renderContent($viewContent) {
        return Application::$app->view->renderContent($viewContent);
    }
}
