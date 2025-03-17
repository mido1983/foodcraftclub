<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;

class SiteController extends Controller {
    public function home() {
        $this->view->title = 'Home';
        return $this->render('home', [
            'welcomeMessage' => 'Welcome to Food Craft Club'
        ]);
    }

    public function error() {
        Application::$app->response->setStatusCode(404);
        $this->view->title = 'Page Not Found';
        return $this->render('_404');
    }
}
