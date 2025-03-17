<?php

namespace App\Core;

use App\Core\Exception\ForbiddenException;

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
        $this->session = new Session();
        $this->db = new Database();
        $this->view = new View();
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
