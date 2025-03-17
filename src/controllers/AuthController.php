<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Models\User;

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        // Protect register route with admin middleware
        $this->registerMiddleware(new AuthMiddleware(['register'], ['admin']));
    }

    public function login() {
        $this->view->title = 'Login';
        
        if (Application::$app->request->isPost()) {
            $email = Application::$app->request->getBody()['email'] ?? '';
            $password = Application::$app->request->getBody()['password'] ?? '';
            
            $user = User::findOne(['email' => $email]);
            
            if ($user && $user->verifyPassword($password)) {
                if ($user->status !== 'active') {
                    Application::$app->session->setFlash('error', 'Your account is not active');
                    return $this->render('auth/login');
                }
                
                Application::$app->session->setUser($user);
                Application::$app->session->setFlash('success', 'Welcome back!');
                
                // Redirect based on role
                if ($user->hasRole('admin')) {
                    return Application::$app->response->redirect('/admin');
                } elseif ($user->hasRole('seller')) {
                    return Application::$app->response->redirect('/seller/dashboard');
                } else {
                    return Application::$app->response->redirect('/');
                }
            }
            
            Application::$app->session->setFlash('error', 'Invalid email or password');
        }
        
        // If user is already logged in, redirect to home
        if (Application::$app->session->isLoggedIn()) {
            return Application::$app->response->redirect('/');
        }
        
        return $this->render('auth/login');
    }
    
    public function register() {
        $this->view->title = 'Create New User';
        
        if (Application::$app->request->isPost()) {
            $data = Application::$app->request->getBody();
            
            // Validate input
            $errors = $this->validateRegistration($data);
            
            if (empty($errors)) {
                $user = new User();
                $user->email = $data['email'];
                $user->setPassword($data['password']);
                $user->full_name = $data['full_name'];
                $user->status = $data['status'] ?? 'active';
                
                try {
                    if ($user->save()) {
                        // Get selected roles from the form or default to client
                        $selectedRoles = isset($data['roles']) && is_array($data['roles']) 
                            ? $data['roles'] 
                            : [3]; // Default to client role (ID: 3)
                        
                        $user->setRoles($selectedRoles);
                        
                        Application::$app->session->setFlash('success', 'User created successfully!');
                        return Application::$app->response->redirect('/admin/users');
                    }
                } catch (\Exception $e) {
                    Application::$app->session->setFlash('error', 'Failed to create user. Please try again.');
                }
            } else {
                Application::$app->session->setFlash('error', implode('<br>', $errors));
            }
        }
        
        return $this->render('auth/register');
    }
    
    public function logout() {
        Application::$app->session->destroy();
        Application::$app->session->setFlash('success', 'You have been logged out');
        return Application::$app->response->redirect('/');
    }
    
    private function validateRegistration(array $data): array {
        $errors = [];
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (User::findOne(['email' => $data['email']])) {
            $errors[] = 'Email already exists';
        }
        
        // Password validation
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } elseif ($data['password'] !== ($data['password_confirm'] ?? '')) {
            $errors[] = 'Passwords do not match';
        }
        
        // Full name validation
        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required';
        }
        
        // Status validation
        $validStatuses = ['active', 'pending', 'suspended'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = 'Invalid status selected';
        }
        
        return $errors;
    }
}
