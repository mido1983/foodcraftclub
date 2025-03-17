<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Models\User;
use PDO;

class AdminController extends Controller {
    public function __construct() {
        parent::__construct();
        // Protect all admin routes with admin middleware
        $this->registerMiddleware(new AuthMiddleware(['*'], ['admin']));
    }

    public function index() {
        $this->view->title = 'Admin Dashboard';
        
        // Get counts for dashboard stats
        $userCount = $this->getUserCount();
        $sellerCount = $this->getSellerCount();
        $clientCount = $this->getClientCount();
        
        return $this->render('admin/dashboard', [
            'userCount' => $userCount,
            'sellerCount' => $sellerCount,
            'clientCount' => $clientCount
        ]);
    }
    
    public function users() {
        $this->view->title = 'Manage Users';
        
        // Get all users with their roles
        $users = $this->getAllUsers();
        
        return $this->render('admin/users', [
            'users' => $users
        ]);
    }
    
    public function editUser($id) {
        $this->view->title = 'Edit User';
        
        // Convert id to integer
        $id = (int)$id;
        
        $user = User::findOne(['id' => $id]);
        if (!$user) {
            Application::$app->session->setFlash('error', 'User not found');
            return Application::$app->response->redirect('/admin/users');
        }
        
        // Get all available roles
        $allRoles = $this->getAllRoles();
        
        // Get user roles directly from the database
        $db = Application::$app->db;
        $stmt = $db->prepare("
            SELECT r.* 
            FROM roles r
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $user->id]);
        $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure role IDs are integers
        foreach ($userRoles as &$role) {
            $role['id'] = (int)$role['id'];
        }
        
        // Debug log
        Application::$app->logger->info('User ID ' . $id . ' current roles: ' . print_r($userRoles, true), [], 'users.log');
        
        // Handle form submission
        if (Application::$app->request->isPost()) {
            $data = Application::$app->request->getBody();
            
            // Debug data received from form
            Application::$app->logger->debug('Form data received for user ID ' . $id . ': ' . print_r($data, true), [], 'users.log');
            
            // Validate input
            $errors = $this->validateUserEdit($data, $user);
            
            if (empty($errors)) {
                // Update user data
                $user->email = $data['email'];
                $user->full_name = $data['full_name'];
                
                // Улучшенная обработка статуса с подробной отладкой
                $oldStatus = $user->status;
                if (isset($data['status']) && in_array($data['status'], ['active', 'pending', 'suspended'])) {
                    $user->status = $data['status'];
                    Application::$app->logger->info('Статус пользователя изменен с "' . $oldStatus . '" на "' . $data['status'] . '"', ['user_id' => $user->id], 'status.log');
                    
                    // Прямое обновление статуса в базе данных
                    try {
                        $directStatusUpdate = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
                        $directResult = $directStatusUpdate->execute([
                            'id' => $user->id,
                            'status' => $data['status']
                        ]);
                        Application::$app->logger->info('Прямое обновление статуса: ' . ($directResult ? 'успешно' : 'ошибка'), ['user_id' => $user->id], 'status.log');
                        
                        // Проверяем, что статус действительно обновился
                        $checkStmt = $db->prepare("SELECT status FROM users WHERE id = :id");
                        $checkStmt->execute(['id' => $user->id]);
                        $updatedStatus = $checkStmt->fetchColumn();
                        Application::$app->logger->info('Проверка статуса после прямого обновления: ' . $updatedStatus, ['user_id' => $user->id], 'status.log');
                    } catch (\Exception $e) {
                        Application::$app->logger->error('Ошибка при прямом обновлении статуса: ' . $e->getMessage(), ['user_id' => $user->id], 'status.log');
                    }
                } else {
                    Application::$app->logger->error('Ошибка: Неверный статус "' . ($data['status'] ?? 'не указан') . '". Используем текущий статус: "' . $user->status . '"', ['user_id' => $user->id], 'status.log');
                }
                
                // Update password if provided
                if (!empty($data['password']) && $data['password'] === $data['password_confirm']) {
                    $user->setPassword($data['password']);
                }
                
                try {
                    // Update user data in the database
                    if ($user->save()) {
                        // Get selected role from the form (now using radio buttons)
                        $selectedRoleId = null;
                        if (isset($data['role']) && is_numeric($data['role'])) {
                            $selectedRoleId = (int)$data['role'];
                            Application::$app->logger->info('Selected role from form: ' . $selectedRoleId, ['user_id' => $user->id], 'users.log');
                        } else {
                            Application::$app->logger->warning('No role selected in form or invalid role', ['user_id' => $user->id], 'users.log');
                            // Default to client role if none selected
                            $selectedRoleId = 3; // Client role ID
                            Application::$app->logger->info('Using default client role ID: ' . $selectedRoleId, ['user_id' => $user->id], 'users.log');
                        }
                        
                        // Update roles directly in the database
                        try {
                            $db->beginTransaction();
                            
                            // Delete existing roles for the user
                            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
                            $stmt->execute(['user_id' => $user->id]);
                            
                            // Add the selected role
                            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                            $stmt->execute(['user_id' => $user->id, 'role_id' => $selectedRoleId]);
                            
                            $db->commit();
                            
                            // Get the role name for the success message
                            $roleName = '';
                            foreach ($allRoles as $role) {
                                if ((int)$role['id'] === (int)$selectedRoleId) {
                                    $roleName = $role['name'];
                                    break;
                                }
                            }
                            
                            Application::$app->logger->info('Role updated successfully in AdminController for user ID: ' . $user->id . ' with role: ' . $roleName, [], 'users.log');
                            
                            // Формируем сообщение об успехе с информацией о роли и статусе
                            $statusLabel = '';
                            switch ($user->status) {
                                case 'active':
                                    $statusLabel = 'Активный';
                                    break;
                                case 'pending':
                                    $statusLabel = 'Ожидает';
                                    break;
                                case 'suspended':
                                    $statusLabel = 'Заблокирован';
                                    break;
                            }
                            
                            $successMessage = 'User updated successfully! ' .
                                             'Role: ' . $roleName . ', ' .
                                             'Status: ' . $statusLabel;
                            
                            Application::$app->session->setFlash('success', $successMessage);
                            return Application::$app->response->redirect('/admin/users');
                        } catch (\Exception $e) {
                            $db->rollBack();
                            Application::$app->logger->error('Error updating role in AdminController: ' . $e->getMessage(), ['user_id' => $user->id], 'users.log');
                            throw $e;
                        }
                    }
                } catch (\Exception $e) {
                    Application::$app->logger->error('Error updating user: ' . $e->getMessage(), ['user_id' => $user->id], 'users.log');
                    Application::$app->session->setFlash('error', 'Failed to update user: ' . $e->getMessage());
                }
            } else {
                // Set errors in session
                Application::$app->session->setFlash('errors', $errors);
                Application::$app->logger->warning('Validation errors: ' . print_r($errors, true), ['user_id' => $user->id], 'users.log');
            }
        }
        
        return $this->render('admin/edit-user', [
            'user' => $user,
            'userRoles' => $userRoles,
            'allRoles' => $allRoles
        ]);
    }
    
    public function deleteUser($id) {
        // Prevent deleting your own account
        if ((int)$id === Application::$app->session->getUserId()) {
            Application::$app->session->setFlash('error', 'You cannot delete your own account');
            return Application::$app->response->redirect('/admin/users');
        }
        
        $user = User::findOne(['id' => $id]);
        if (!$user) {
            Application::$app->session->setFlash('error', 'User not found');
            return Application::$app->response->redirect('/admin/users');
        }
        
        try {
            // Delete user roles first
            $db = Application::$app->db;
            $statement = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $statement->execute(['user_id' => $id]);
            
            // Then delete the user
            $statement = $db->prepare("DELETE FROM users WHERE id = :id");
            if ($statement->execute(['id' => $id])) {
                Application::$app->session->setFlash('success', 'User deleted successfully');
            } else {
                Application::$app->session->setFlash('error', 'Failed to delete user');
            }
        } catch (\Exception $e) {
            Application::$app->logger->error('Error deleting user: ' . $e->getMessage(), ['user_id' => $id], 'users.log');
            Application::$app->session->setFlash('error', 'Error deleting user: ' . $e->getMessage());
        }
        
        return Application::$app->response->redirect('/admin/users');
    }
    
    public function clearCache() {
        // Clear PHP opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear application cache
        $db = Application::$app->db;
        
        try {
            // Clear user roles cache by forcing a reload for all users
            $stmt = $db->prepare("SELECT id FROM users");
            $stmt->execute();
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($userIds as $userId) {
                // Force reload of user roles
                $stmt = $db->prepare("
                    SELECT r.* 
                    FROM roles r
                    JOIN user_roles ur ON r.id = ur.role_id
                    WHERE ur.user_id = :user_id
                ");
                $stmt->execute(['user_id' => $userId]);
                
                Application::$app->logger->info("Cleared cache for user ID: {$userId}", [], 'cache.log');
            }
            
            // Log cache clearing
            Application::$app->logger->info('Cache cleared successfully', [], 'cache.log');
            
            Application::$app->session->setFlash('success', 'Cache cleared successfully!');
        } catch (\Exception $e) {
            Application::$app->logger->error('Error clearing cache: ' . $e->getMessage(), [], 'cache.log');
            Application::$app->session->setFlash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
        
        return Application::$app->response->redirect('/admin/users');
    }
    
    private function validateUserEdit(array $data, User $user): array {
        $errors = [];
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($data['email'] !== $user->email) {
            // Only check for duplicate email if it has changed
            $existingUser = User::findOne(['email' => $data['email']]);
            if ($existingUser !== null) {
                $errors[] = 'Email already exists';
            }
        }
        
        // Password validation - only if password is being changed
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } elseif ($data['password'] !== ($data['password_confirm'] ?? '')) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // Full name validation
        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required';
        }
        
        return $errors;
    }
    
    private function getAllRoles(): array {
        $db = Application::$app->db;
        $statement = $db->prepare("SELECT * FROM roles ORDER BY name");
        $statement->execute();
        $roles = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure role IDs are integers
        foreach ($roles as &$role) {
            $role['id'] = (int)$role['id'];
        }
        
        return $roles;
    }
    
    private function getUserCount(): int {
        $db = Application::$app->db;
        $statement = $db->prepare("SELECT COUNT(*) as count FROM users");
        $statement->execute();
        $result = $statement->fetch();
        return (int)($result['count'] ?? 0);
    }
    
    private function getSellerCount(): int {
        $db = Application::$app->db;
        $statement = $db->prepare("
            SELECT COUNT(DISTINCT ur.user_id) as count 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE r.name = 'seller'
        ");
        $statement->execute();
        $result = $statement->fetch();
        return (int)($result['count'] ?? 0);
    }
    
    private function getClientCount(): int {
        $db = Application::$app->db;
        $statement = $db->prepare("
            SELECT COUNT(DISTINCT ur.user_id) as count 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE r.name = 'client'
        ");
        $statement->execute();
        $result = $statement->fetch();
        return (int)($result['count'] ?? 0);
    }
    
    private function getAllUsers(): array {
        $db = Application::$app->db;
        $statement = $db->prepare("
            SELECT u.*, GROUP_CONCAT(r.name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            GROUP BY u.id
            ORDER BY u.id DESC
        ");
        $statement->execute();
        return $statement->fetchAll();
    }
}
