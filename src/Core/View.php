<?php

namespace App\Core;

class View {
    public string $title = '';

    public function renderView($view, $params = []) {
        $viewContent = $this->renderViewOnly($view, $params);
        $layoutContent = $this->layoutContent();
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    public function renderContent($viewContent) {
        $layoutContent = $this->layoutContent();
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected function layoutContent() {
        $layout = Application::$app->controller?->layout ?? 'main';
        ob_start();
        include_once Application::$app->rootPath . "/src/Views/layouts/$layout.php";
        return ob_get_clean();
    }

    protected function renderViewOnly($view, $params) {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        include_once Application::$app->rootPath . "/src/Views/$view.php";
        return ob_get_clean();
    }
    
    /**
     * Get the appropriate Bootstrap badge class for an order status
     * 
     * @param string $status Order status
     * @return string Bootstrap badge class
     */
    public function getStatusBadgeClass(string $status): string {
        return match (strtolower($status)) {
            'new', 'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
