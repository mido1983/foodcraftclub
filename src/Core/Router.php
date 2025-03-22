<?php

namespace App\Core;

class Router {
    private array $routes = [];
    private array $middlewares = [];

    public function __construct(
        private Request $request,
        private Response $response
    ) {}

    public function get(string $path, $callback): self {
        $this->routes['get'][$path] = $callback;
        return $this;
    }

    public function post(string $path, $callback): self {
        $this->routes['post'][$path] = $callback;
        return $this;
    }

    public function middleware($middleware): self {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Check if a route matches a path
     * 
     * @param string $route
     * @param string $path
     * @return bool
     */
    public function matchRoute(string $route, string $path): bool {
        if ($route === $path) {
            return true;
        }
        
        $routePattern = $this->convertRouteToRegex($route);
        return (bool) preg_match($routePattern, $path);
    }

    /**
     * Find matching route for path and method
     * 
     * @param string $path
     * @param string $method
     * @return array|null
     */
    public function findMatchingRoute(string $path, string $method): ?array {
        // Convert method to lowercase to match keys in routes array
        $method = strtolower($method);
        
        // Check for exact match
        if (isset($this->routes[$method][$path])) {
            return [
                'callback' => $this->routes[$method][$path],
                'params' => []
            ];
        }
        
        // Check dynamic routes
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $path)) {
                return [
                    'callback' => $handler,
                    'params' => $this->extractParams($route, $path)
                ];
            }
        }
        
        return null;
    }

    public function resolve() {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        
        // Run global middlewares first - BEFORE any route matching
        // This ensures security checks happen before any controller is instantiated
        foreach ($this->middlewares as $middleware) {
            $middleware($this->request, $this->response);
        }
        
        // Find matching route
        $matchingRoute = $this->findMatchingRoute($path, $method);
        
        if (!$matchingRoute) {
            $this->response->setStatusCode(404);
            return $this->renderView('_404');
        }
        
        $callback = $matchingRoute['callback'];
        $params = $matchingRoute['params'];

        if (is_array($callback)) {
            /** @var \App\Core\Controller $controller */
            $controller = new $callback[0]();
            Application::$app->setController($controller);
            $controller->action = $callback[1];
            $callback[0] = $controller;

            // Execute controller middlewares BEFORE calling the action
            // This is critical for security - ensures all middleware checks are performed
            foreach ($controller->getMiddlewares() as $middleware) {
                // If middleware returns false, stop execution
                if ($middleware->execute() === false) {
                    return $this->response->redirect('/login');
                }
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

    public function renderView($view, $params = []): string {
        return Application::$app->view->renderView($view, $params);
    }

    public function renderContent($viewContent): string {
        return Application::$app->view->renderContent($viewContent);
    }
}
