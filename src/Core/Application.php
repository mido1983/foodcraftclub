<?php

namespace App\Core;

use App\Core\Exception\ForbiddenException;
use App\Core\Middleware\AdminRouteMiddleware;

class Application {
    public static Application $app;
    public Router $router;
    public Request $request;
    public Response $response;
    public Database $db;
    public ?Controller $controller = null;
    public Session $session;
    public View $view;

    public function __construct(public string $rootPath) {
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database(); // Initialize database first
        $this->session = new Session(); // Then initialize session
        $this->view = new View();
        
        // Register global middlewares
        $this->router->middleware(new AdminRouteMiddleware());
    }

    public function run() {
        try {
            echo $this->router->resolve();
        } catch (ForbiddenException $e) {
            $this->response->setStatusCode(403);
            echo $this->view->renderView('_403', [
                'exception' => $e
            ]);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            echo $this->view->renderView('_error', [
                'exception' => $e
            ]);
        }
    }

    public function getController() {
        return $this->controller;
    }

    public function setController(Controller $controller) {
        $this->controller = $controller;
    }
}
