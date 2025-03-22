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

    /**
     * Initialize application with dependencies
     * @param string $rootPath Root path of the application
     */
    public function __construct(public string $rootPath) {
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database(); // Initialize database first
        $this->session = new Session(); // Then initialize session
        $this->view = new View();
        $this->logger = new Logger();
        
        // Initialize error handler
        $this->errorHandler = new ErrorHandler($this->logger, false); // false for production, true for development
        
        // Register global middlewares
        $this->router->middleware(new AdminRouteMiddleware());
    }

    /**
     * Run the application
     * @return void
     */
    public function run(): void {
        try {
            // Log application start
            $this->logger->info('Application started', ['uri' => $_SERVER['REQUEST_URI']], 'app.log');
            
            // Set HTML content type with UTF-8 encoding for all pages
            $this->response->setHtmlContentType();
            
            echo $this->router->resolve();
        } catch (ForbiddenException $e) {
            // Log access error
            $this->logger->warning(
                'Access error: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            $this->response->setStatusCode(403);
            echo $this->view->renderView('_403', [
                'exception' => $e
            ]);
        } catch (\Exception $e) {
            // Log general error
            $this->logger->error(
                'Application error: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            $this->response->setStatusCode(500);
            echo $this->view->renderView('_error', [
                'exception' => $e
            ]);
        }
    }

    /**
     * Get the current controller
     * @return Controller|null
     */
    public function getController(): ?Controller {
        return $this->controller;
    }

    /**
     * Set the current controller
     * @param Controller $controller
     */
    public function setController(Controller $controller): void {
        $this->controller = $controller;
    }
}
