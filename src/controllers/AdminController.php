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
        
        // Get user roles
        $userRoles = $user->getRoles();
        // Extract just the IDs from the roles array for comparison
        $roleIds = array_map(function($role) {
            return (int)$role['id'];
        }, $userRoles);
        
        // Handle form submission
        if (Application::$app->request->isPost()) {
            $data = Application::$app->request->getBody();
            
            // Debug data received from form
            error_log('Form data received: ' . print_r($data, true));
            
            // Validate input
            $errors = $this->validateUserEdit($data, $user);
            
            if (empty($errors)) {
                // Update user data
                $user->email = $data['email'];
                $user->full_name = $data['full_name'];
                
                // Ensure status is properly set
                if (isset($data['status']) && in_array($data['status'], ['active', 'pending', 'suspended'])) {
                    $user->status = $data['status'];
                }
                
                // Update password if provided
                if (!empty($data['password']) && $data['password'] === $data['password_confirm']) {
                    $user->setPassword($data['password']);
                }
                
                try {
                    // Update user data in the database
                    if ($user->save()) {
                        // Get selected roles from the form
                        $selectedRoles = [];
                        if (isset($data['roles']) && is_array($data['roles'])) {
                            // Convert role IDs to integers
                            foreach ($data['roles'] as $roleId) {
                                $selectedRoles[] = (int)$roleId;
                            }
                        }
                        
                        // If no roles selected, default to client role
                        if (empty($selectedRoles)) {
                            $selectedRoles = [3]; // Client role ID
                        }
                        
                        error_log('Selected roles before setting: ' . implode(', ', $selectedRoles));
                        
                        // Update roles directly in the database
                        $db = Application::$app->db;
                        try {
                            $db->beginTransaction();
                            
                            // Delete existing roles
                            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
                            $stmt->execute(['user_id' => $user->id]);
                            
                            // Insert new roles
                            foreach ($selectedRoles as $roleId) {
                                $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                                $stmt->execute([
                                    'user_id' => $user->id,
                                    'role_id' => $roleId
                                ]);
                            }
                            
                            $db->commit();
                            error_log('Roles updated successfully in AdminController for user ID: ' . $user->id);
                        } catch (\Exception $e) {
                            $db->rollBack();
                            error_log('Error updating roles in AdminController: ' . $e->getMessage());
                            throw $e;
                        }
                        
                        Application::$app->session->setFlash('success', 'User updated successfully!');
                        return Application::$app->response->redirect('/admin/users');
                    }
                } catch (\Exception $e) {
                    Application::$app->session->setFlash('error', 'Failed to update user: ' . $e->getMessage());
                }
            } else {
                Application::$app->session->setFlash('error', implode('<br>', $errors));
            }
        }
        
        // Get all available roles for the form
        $allRoles = $this->getAllRoles();
        
        return $this->render('admin/edit-user', [
            'user' => $user,
            'userRoles' => $roleIds,
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
            Application::$app->session->setFlash('error', 'Error deleting user: ' . $e->getMessage());
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
