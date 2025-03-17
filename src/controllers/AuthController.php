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
            
            // Debug logging
            error_log("Login attempt for email: {$email}");
            
            $user = User::findOne(['email' => $email]);
            
            if (!$user) {
                error_log("User not found with email: {$email}");
                Application::$app->session->setFlash('error', 'Invalid email or password');
                return $this->render('auth/login');
            }
            
            error_log("User found, verifying password for: {$email}");
            
            if ($user && $user->verifyPassword($password)) {
                error_log("Password verified for: {$email}");
                
                if ($user->status !== 'active') {
                    error_log("User account not active: {$email}, status: {$user->status}");
                    Application::$app->session->setFlash('error', 'Your account is not active');
                    return $this->render('auth/login');
                }
                
                Application::$app->session->setUser($user);
                Application::$app->session->setFlash('success', 'Welcome back!');
                
                // Get user roles for debugging
                $roles = $user->getRoles();
                error_log("User roles: " . json_encode($roles));
                
                // Redirect based on role
                if ($user->hasRole('admin')) {
                    error_log("Redirecting admin user to /admin");
                    return Application::$app->response->redirect('/admin');
                } elseif ($user->hasRole('seller')) {
                    error_log("Redirecting seller user to /seller/dashboard");
                    return Application::$app->response->redirect('/seller/dashboard');
                } else {
                    error_log("Redirecting regular user to /");
                    return Application::$app->response->redirect('/');
                }
            } else {
                error_log("Password verification failed for: {$email}");
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
        // Only admin can access this page
        if (!Application::$app->session->hasRole('admin')) {
            Application::$app->session->setFlash('error', 'You do not have permission to access this page');
            return Application::$app->response->redirect('/');
        }
        
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
                        // Get selected roles from the form
                        $selectedRoles = [];
                        if (isset($data['roles']) && is_array($data['roles'])) {
                            // Convert role IDs to integers
                            $selectedRoles = array_map('intval', $data['roles']);
                        }
                        
                        // If no roles selected, default to client role
                        if (empty($selectedRoles)) {
                            $selectedRoles = [3]; // Client role ID
                        }
                        
                        // Make sure we have the user ID before setting roles
                        if ($user->id) {
                            // Always set roles to ensure they're properly assigned
                            $user->setRoles($selectedRoles);
                            
                            Application::$app->session->setFlash('success', 'User created successfully!');
                            return Application::$app->response->redirect('/admin/users');
                        } else {
                            Application::$app->session->setFlash('error', 'Failed to create user: Could not get user ID');
                        }
                    } else {
                        Application::$app->session->setFlash('error', 'Failed to save user to database');
                    }
                } catch (\Exception $e) {
                    Application::$app->session->setFlash('error', 'Failed to create user: ' . $e->getMessage());
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
        } else {
            // Check if email already exists
            $existingUser = User::findOne(['email' => $data['email']]);
            if ($existingUser !== null) {
                $errors[] = 'Email already exists';
            }
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
        
        return $errors;
    }
}
