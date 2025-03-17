<?php

namespace App\Core;

class Request {
    public function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    public function getMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function isGet(): bool {
        return $this->getMethod() === 'get';
    }

    public function isPost(): bool {
        return $this->getMethod() === 'post';
    }

    public function getBody() {
        $body = [];
        
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
        
        return $body;
    }

    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function getJson() {
        if (!$this->isPost()) {
            return null;
        }
        
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }
}
