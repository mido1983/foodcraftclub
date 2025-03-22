<?php

namespace App\Core;

class Request {
    /**
     * Get the current request path
     * @return string Request path
     */
    public function getPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Get the current request method
     * @return string Request method in lowercase
     */
    public function getMethod(): string {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Check if the request method is GET
     * @return bool True if GET method
     */
    public function isGet(): bool {
        return $this->getMethod() === 'get';
    }

    /**
     * Check if the request method is POST
     * @return bool True if POST method
     */
    public function isPost(): bool {
        return $this->getMethod() === 'post';
    }

    /**
     * Get sanitized request body data
     * @return array Sanitized request data
     */
    public function getBody(): array {
        $body = [];
        
        try {
            if ($this->isGet()) {
                foreach ($_GET as $key => $value) {
                    $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
            
            if ($this->isPost()) {
                foreach ($_POST as $key => $value) {
                    $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error processing request body: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
        }
        
        return $body;
    }

    /**
     * Check if the request is an AJAX request
     * @return bool True if AJAX request
     */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Get JSON data from request body
     * @return array|null Decoded JSON data or null on error
     */
    public function getJson(): ?array {
        if (!$this->isPost()) {
            return null;
        }
        
        $json = file_get_contents('php://input');
        try {
            return json_decode($json, true);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error parsing JSON: ' . $e->getMessage(),
                ['uri' => $_SERVER['REQUEST_URI'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return null;
        }
    }
}
