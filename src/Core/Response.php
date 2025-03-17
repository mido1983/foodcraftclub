<?php

namespace App\Core;

class Response {
    public function setStatusCode(int $code) {
        http_response_code($code);
    }

    public function redirect(string $url) {
        header("Location: $url");
        exit;
    }

    public function json($data, int $statusCode = 200) {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function setHeaders(array $headers) {
        foreach ($headers as $header) {
            header($header);
        }
    }
}
