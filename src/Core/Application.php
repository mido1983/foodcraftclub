<?php

namespace App\Core;

use App\Core\Exception\ForbiddenException;
use App\Core\Logger;
use App\Core\Middleware\AdminRouteMiddleware;
use App\Core\ErrorHandler;

class Application {
    public static Application $app;
    public Router $router;
    public Request $request;
    public Response $response;
    public Database $db;
    public ?Controller $controller = null;
    public Session $session;
    public View $view;
    public Logger $logger;
    public ErrorHandler $errorHandler;

    public function __construct(public string $rootPath) {
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database(); // Initialize database first
        $this->session = new Session(); // Then initialize session
        $this->view = new View();
        $this->logger = new Logger();
        
        // Инициализируем обработчик ошибок
        $this->errorHandler = new ErrorHandler($this->logger, false); // false для продакшена, true для разработки
        
        // Register global middlewares
        $this->router->middleware(new AdminRouteMiddleware());
    }

    public function run() {
        try {
            // Логгируем запуск приложения
            $this->logger->info('Запуск приложения', ['uri' => $_SERVER['REQUEST_URI']], 'app.log');
            
            echo $this->router->resolve();
        } catch (ForbiddenException $e) {
            // Логгируем ошибку доступа
            $this->logger->warning(
                'Ошибка доступа: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            $this->response->setStatusCode(403);
            echo $this->view->renderView('_403', [
                'exception' => $e
            ]);
        } catch (\Exception $e) {
            // Логгируем общую ошибку
            $this->logger->error(
                'Ошибка приложения: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
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
