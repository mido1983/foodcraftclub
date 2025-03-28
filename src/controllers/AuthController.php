<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Mailer;
use App\Core\Middleware\AuthMiddleware;
use App\Models\User;
use App\Models\PasswordReset;

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        // Protect register route with admin middleware
        $this->registerMiddleware(new AuthMiddleware(['register'], ['admin']));
    }

    /**
     * Handle user login
     * @return string|void
     */
    public function login(): string|null {
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
    
    /**
     * Handle user registration (admin only)
     * @return string|null
     */
    public function register(): string|null {
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
                            
                            // Профиль продавца создается автоматически в методе setRoles класса User
                            
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
    
    /**
     * Handle user logout
     * @return null
     */
    public function logout(): ?string {
        Application::$app->session->destroy();
        Application::$app->session->setFlash('success', 'You have been logged out');
        return Application::$app->response->redirect('/');
    }
    
    /**
     * Handle forgot password request
     * @return string|null
     */
    public function forgotPassword(): string|null {
        $this->view->title = 'Восстановление пароля';
        
        if (Application::$app->request->isPost()) {
            // Для AJAX запросов
            if (Application::$app->request->isAjax()) {
                $data = Application::$app->request->getJsonBody();
                $email = $data['email'] ?? '';
                
                // Проверка CSRF токена
                $csrfToken = Application::$app->request->getHeader('X-CSRF-Token');
                if (!Application::$app->session->validateCsrfToken($csrfToken)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Недействительный CSRF токен'
                    ]);
                    exit;
                }
            } else {
                // Для обычных POST запросов
                $data = Application::$app->request->getBody();
                $email = $data['email'] ?? '';
            }
            
            // Проверка формата email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Пожалуйста, введите корректный email'
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', 'Пожалуйста, введите корректный email');
                    return $this->render('auth/forgot-password');
                }
            }
            
            // Поиск пользователя
            $user = User::findOne(['email' => $email]);
            
            if ($user) {
                // Создаем токен для сброса пароля
                $passwordReset = new PasswordReset();
                $passwordReset->user_id = $user->id;
                $passwordReset->token = bin2hex(random_bytes(32)); // 64-символьный токен
                
                // Устанавливаем срок действия токена на 24 часа от текущего времени
                $expiryTime = new \DateTime();
                $expiryTime->modify('+24 hours');
                $passwordReset->expires_at = $expiryTime->format('Y-m-d H:i:s');
                
                $passwordReset->save();
                
                // Логируем действие
                Application::$app->logger->info(
                    'Password reset requested', 
                    ['user_id' => $user->id, 'email' => $email],
                    'users.log'
                );
                
                // Отправка email с ссылкой для сброса пароля
                $mailer = new Mailer();
                $resetUrl = Application::$app->request->getBaseUrl() . '/reset-password?token=' . $passwordReset->token;
                
                // Логирование ссылки для сброса пароля
                Application::$app->logger->info(
                    'Reset password link', 
                    ['user_id' => $user->id, 'reset_link' => $resetUrl],
                    'users.log'
                );
                
                // Отправляем электронное письмо
                $mailer->sendPasswordResetEmail($email, $passwordReset->token, $user->name ?? 'Пользователь');
            }
            
            // Всегда возвращаем успешный ответ для безопасности
            if (Application::$app->request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Если указанный email зарегистрирован в системе, мы отправили на него инструкции по восстановлению пароля'
                ]);
                exit;
            } else {
                Application::$app->session->setFlash('success', 'Если указанный email зарегистрирован в системе, мы отправили на него инструкции по восстановлению пароля');
                return Application::$app->response->redirect('/login');
            }
        }
        
        return $this->render('auth/forgot-password');
    }
    
    /**
     * Handle password reset
     * @return string|null
     */
    public function resetPassword(): string|null {
        $this->view->title = 'Сброс пароля';
        
        // Обработка GET запроса - отображение формы сброса пароля
        if (Application::$app->request->isGet()) {
            $params = Application::$app->request->getBody();
            $token = $params['token'] ?? '';
            
            if (empty($token)) {
                Application::$app->session->setFlash('error', 'Недействительный токен сброса пароля');
                return Application::$app->response->redirect('/login');
            }
            
            // Проверка валидности токена
            $token = trim($token);
            
            // Логируем полученный токен для сброса пароля
            Application::$app->logger->info(
                'Получен токен для сброса пароля',
                ['token' => $token, 'token_length' => strlen($token)],
                'users.log'
            );
            
            // Используем PDO для безопасного выполнения SQL-запроса
            $db = Application::$app->db;
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = :token");
            $stmt->execute(['token' => $token]);
            $passwordReset = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($passwordReset) {
                // Преобразуем данные из базы в объект модели
                $resetModel = new PasswordReset();
                foreach ($passwordReset as $key => $value) {
                    if (property_exists($resetModel, $key)) {
                        $resetModel->{$key} = $value;
                    }
                }
                $passwordReset = $resetModel;
            }
            
            // Логируем результат поиска токена
            Application::$app->logger->info(
                'Результат поиска токена',
                ['found' => (bool)$passwordReset, 'token' => $token],
                'users.log'
            );
            
            if (!$passwordReset || strtotime($passwordReset->expires_at) < time()) {
                Application::$app->session->setFlash('error', 'Токен сброса пароля недействителен или истек срок его действия');
                return Application::$app->response->redirect('/login');
            }
            
            return $this->render('auth/reset-password', ['token' => $token]);
        }
        
        // Обработка POST запроса - сброс пароля
        if (Application::$app->request->isPost()) {
            // Для AJAX запросов
            if (Application::$app->request->isAjax()) {
                $data = Application::$app->request->getJsonBody();
                $token = $data['token'] ?? '';
                $password = $data['password'] ?? '';
                $passwordConfirm = $data['password_confirm'] ?? '';
                
                // Проверка CSRF токена
                $csrfToken = Application::$app->request->getHeader('X-CSRF-Token');
                if (!Application::$app->session->validateCsrfToken($csrfToken)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Недействительный CSRF токен'
                    ]);
                    exit;
                }
            } else {
                // Для обычных POST запросов
                $data = Application::$app->request->getBody();
                $token = $data['token'] ?? '';
                $password = $data['password'] ?? '';
                $passwordConfirm = $data['password_confirm'] ?? '';
            }
            
            // Проверка наличия данных
            if (empty($token) || empty($password) || empty($passwordConfirm)) {
                $errorMessage = 'Все поля обязательны для заполнения';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return $this->render('auth/reset-password', ['token' => $token]);
                }
            }
            
            // Проверка совпадения паролей
            if ($password !== $passwordConfirm) {
                $errorMessage = 'Пароли не совпадают';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return $this->render('auth/reset-password', ['token' => $token]);
                }
            }
            
            // Проверка сложности пароля
            if (strlen($password) < 8) {
                $errorMessage = 'Пароль должен быть не менее 8 символов';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return $this->render('auth/reset-password', ['token' => $token]);
                }
            }
            
            // Проверка валидности токена
            $token = trim($token);
            
            // Логируем полученный токен для сброса пароля
            Application::$app->logger->info(
                'Получен токен для сброса пароля',
                ['token' => $token, 'token_length' => strlen($token)],
                'users.log'
            );
            
            // Используем PDO для безопасного выполнения SQL-запроса
            $db = Application::$app->db;
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = :token");
            $stmt->execute(['token' => $token]);
            $passwordReset = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($passwordReset) {
                // Преобразуем данные из базы в объект модели
                $resetModel = new PasswordReset();
                foreach ($passwordReset as $key => $value) {
                    if (property_exists($resetModel, $key)) {
                        $resetModel->{$key} = $value;
                    }
                }
                $passwordReset = $resetModel;
            }
            
            // Логируем результат поиска токена
            Application::$app->logger->info(
                'Результат поиска токена',
                ['found' => (bool)$passwordReset, 'token' => $token],
                'users.log'
            );
            
            if (!$passwordReset || strtotime($passwordReset->expires_at) < time()) {
                $errorMessage = 'Токен сброса пароля недействителен или истек срок его действия';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return Application::$app->response->redirect('/login');
                }
            }
            
            // Поиск пользователя
            $user = User::findOne(['id' => $passwordReset->user_id]);
            
            if (!$user) {
                $errorMessage = 'Пользователь не найден';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return Application::$app->response->redirect('/login');
                }
            }
            
            // Обновление пароля
            $user->setPassword($password);
            $result = $user->save();
            
            if ($result) {
                // Удаление записи о сбросе пароля
                $passwordReset->delete();
                
                // Логирование успешного сброса пароля
                Application::$app->logger->info(
                    'Password reset successful', 
                    ['user_id' => $user->id],
                    'users.log'
                );
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Пароль успешно изменен'
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('success', 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.');
                    return Application::$app->response->redirect('/login');
                }
            } else {
                $errorMessage = 'Ошибка при обновлении пароля';
                
                if (Application::$app->request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    Application::$app->session->setFlash('error', $errorMessage);
                    return $this->render('auth/reset-password', ['token' => $token]);
                }
            }
        }
        
        return Application::$app->response->redirect('/login');
    }
    
    /**
     * Validate registration data
     * @param array $data Registration form data
     * @return array List of validation errors
     */
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
