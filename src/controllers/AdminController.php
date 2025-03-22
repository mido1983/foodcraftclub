<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Models\User;
use Exception;
use PDO;

class AdminController extends Controller {
    /**
     * Constructor - registers middleware for admin authentication
     */
    public function __construct() {
        parent::__construct();
        // Protect all admin routes with admin middleware
        $this->registerMiddleware(new AuthMiddleware(['*'], ['admin']));
    }

    /**
     * Display admin dashboard page
     * @return string Rendered view
     */
    public function index(): string {
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
    
    /**
     * Display users management page
     * @return string Rendered view
     */
    public function users(): string {
        $this->view->title = 'Manage Users';
        
        try {
            // Get all users with their roles
            $users = $this->getAllUsers();
            
            return $this->render('admin/users', [
                'users' => $users
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error loading users page: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'Failed to load users. Please try again.');
            return Application::$app->response->redirect('/admin');
        }
    }
    
    /**
     * Edit user page and processing
     * @param int $id User ID
     * @return string Rendered view or redirect
     */
    public function editUser($id): string {
        $this->view->title = 'Edit User';
        
        try {
            // Convert id to integer
            $id = (int)$id;
            
            $user = User::findOne(['id' => $id]);
            if (!$user) {
                Application::$app->logger->warning(
                    'User not found when attempting to edit', 
                    ['user_id' => $id],
                    'users.log'
                );
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
            Application::$app->logger->info(
                'User ID ' . $id . ' current roles', 
                ['roles' => $userRoles],
                'users.log'
            );
            
            // Handle form submission
            if (Application::$app->request->isPost()) {
                $data = Application::$app->request->getBody();
                
                // Debug data received from form
                Application::$app->logger->debug(
                    'Form data received for user ID ' . $id, 
                    ['data' => $data],
                    'users.log'
                );
                
                // Validate input
                $errors = $this->validateUserEdit($data, $user);
                
                if (empty($errors)) {
                    // Update user data
                    $user->email = $data['email'];
                    $user->full_name = $data['full_name'];
                    
                    // Enhanced status handling with detailed debugging
                    $oldStatus = $user->status;
                    if (isset($data['status']) && in_array($data['status'], ['active', 'pending', 'suspended'])) {
                        $user->status = $data['status'];
                        Application::$app->logger->info(
                            'User status changed from "' . $oldStatus . '" to "' . $data['status'] . '"', 
                            ['user_id' => $user->id],
                            'status.log'
                        );
                        
                        // Direct status update in the database
                        try {
                            $directStatusUpdate = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
                            $directResult = $directStatusUpdate->execute([
                                'id' => $user->id,
                                'status' => $data['status']
                            ]);
                            Application::$app->logger->info(
                                'Direct status update: ' . ($directResult ? 'successful' : 'failed'), 
                                ['user_id' => $user->id],
                                'status.log'
                            );
                            
                            // Verify that the status was actually updated
                            $checkStmt = $db->prepare("SELECT status FROM users WHERE id = :id");
                            $checkStmt->execute(['id' => $user->id]);
                            $updatedStatus = $checkStmt->fetchColumn();
                            Application::$app->logger->info(
                                'Status check after direct update: ' . $updatedStatus, 
                                ['user_id' => $user->id],
                                'status.log'
                            );
                        } catch (Exception $e) {
                            Application::$app->logger->error(
                                'Error during direct status update: ' . $e->getMessage(), 
                                ['user_id' => $user->id, 'trace' => $e->getTraceAsString()],
                                'errors.log'
                            );
                        }
                    } else {
                        Application::$app->logger->error(
                            'Error: Invalid status "' . ($data['status'] ?? 'not specified') . '". Using current status: "' . $user->status . '"', 
                            ['user_id' => $user->id],
                            'errors.log'
                        );
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
                                Application::$app->logger->info(
                                    'Selected role from form: ' . $selectedRoleId, 
                                    ['user_id' => $user->id],
                                    'users.log'
                                );
                            } else {
                                Application::$app->logger->warning(
                                    'No role selected in form or invalid role', 
                                    ['user_id' => $user->id],
                                    'users.log'
                                );
                                // Default to client role if none selected
                                $selectedRoleId = 3; // Client role ID
                                Application::$app->logger->info(
                                    'Using default client role ID: ' . $selectedRoleId, 
                                    ['user_id' => $user->id],
                                    'users.log'
                                );
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
                                
                                Application::$app->logger->info(
                                    'Role updated successfully in AdminController for user ID: ' . $user->id . ' with role: ' . $roleName, 
                                    [],
                                    'users.log'
                                );
                                
                                // Create success message with role and status information
                                $statusLabel = '';
                                switch ($user->status) {
                                    case 'active':
                                        $statusLabel = 'Active';
                                        break;
                                    case 'pending':
                                        $statusLabel = 'Pending';
                                        break;
                                    case 'suspended':
                                        $statusLabel = 'Suspended';
                                        break;
                                }
                                
                                $successMessage = 'User updated successfully! ' .
                                                 'Role: ' . $roleName . ', ' .
                                                 'Status: ' . $statusLabel;
                                
                                Application::$app->session->setFlash('success', $successMessage);
                                return Application::$app->response->redirect('/admin/users');
                            } catch (Exception $e) {
                                $db->rollBack();
                                Application::$app->logger->error(
                                    'Error updating role in AdminController: ' . $e->getMessage(), 
                                    ['user_id' => $user->id, 'trace' => $e->getTraceAsString()],
                                    'errors.log'
                                );
                                throw $e;
                            }
                        }
                    } catch (Exception $e) {
                        Application::$app->logger->error(
                            'Error updating user: ' . $e->getMessage(), 
                            ['user_id' => $user->id, 'trace' => $e->getTraceAsString()],
                            'errors.log'
                        );
                        Application::$app->session->setFlash('error', 'Failed to update user: ' . $e->getMessage());
                    }
                } else {
                    // Display validation errors
                    $errorMessage = 'Please fix the following errors: ' . implode(', ', $errors);
                    Application::$app->session->setFlash('error', $errorMessage);
                }
            }
            
            return $this->render('admin/edit-user', [
                'user' => $user,
                'userRoles' => $userRoles,
                'allRoles' => $allRoles
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error in editUser: ' . $e->getMessage(), 
                ['user_id' => $id, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'An error occurred while editing the user. Please try again.');
            return Application::$app->response->redirect('/admin/users');
        }
    }
    
    /**
     * Delete user action
     * @return string Redirect response
     */
    public function deleteUser(): string {
        try {
            if (!Application::$app->request->isPost()) {
                Application::$app->logger->warning(
                    'Attempt to delete user with non-POST method', 
                    ['method' => Application::$app->request->getMethod()],
                    'security.log'
                );
                Application::$app->session->setFlash('error', 'Invalid request method');
                return Application::$app->response->redirect('/admin/users');
            }
            
            $data = Application::$app->request->getBody();
            
            if (!isset($data['id']) || !is_numeric($data['id'])) {
                Application::$app->logger->warning(
                    'Attempt to delete user without valid ID', 
                    ['data' => $data],
                    'security.log'
                );
                Application::$app->session->setFlash('error', 'Invalid user ID');
                return Application::$app->response->redirect('/admin/users');
            }
            
            $userId = (int)$data['id'];
            
            // Check if user exists
            $user = User::findOne(['id' => $userId]);
            if (!$user) {
                Application::$app->logger->warning(
                    'Attempt to delete non-existent user', 
                    ['user_id' => $userId],
                    'security.log'
                );
                Application::$app->session->setFlash('error', 'User not found');
                return Application::$app->response->redirect('/admin/users');
            }
            
            // Don't allow deleting the current user
            if (Application::$app->session->get('user') && Application::$app->session->get('user') === $userId) {
                Application::$app->logger->warning(
                    'Attempt to delete current logged-in user', 
                    ['user_id' => $userId],
                    'security.log'
                );
                Application::$app->session->setFlash('error', 'You cannot delete your own account');
                return Application::$app->response->redirect('/admin/users');
            }
            
            // Delete user
            if ($user->delete()) {
                Application::$app->logger->info(
                    'User deleted successfully', 
                    ['user_id' => $userId, 'email' => $user->email],
                    'users.log'
                );
                Application::$app->session->setFlash('success', 'User deleted successfully');
            } else {
                Application::$app->logger->error(
                    'Failed to delete user', 
                    ['user_id' => $userId, 'email' => $user->email],
                    'errors.log'
                );
                Application::$app->session->setFlash('error', 'Failed to delete user');
            }
            
            return Application::$app->response->redirect('/admin/users');
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error in deleteUser: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'An error occurred while deleting the user. Please try again.');
            return Application::$app->response->redirect('/admin/users');
        }
    }
    
    /**
     * Get count of all users
     * @return int User count
     */
    private function getUserCount(): int {
        try {
            $stmt = Application::$app->db->prepare("SELECT COUNT(*) FROM users");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting user count: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return 0;
        }
    }
    
    /**
     * Get count of users with seller role
     * @return int Seller count
     */
    private function getSellerCount(): int {
        try {
            $stmt = Application::$app->db->prepare("
                SELECT COUNT(DISTINCT u.id) 
                FROM users u
                JOIN user_roles ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name = 'seller'
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting seller count: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return 0;
        }
    }
    
    /**
     * Get count of users with client role
     * @return int Client count
     */
    private function getClientCount(): int {
        try {
            $stmt = Application::$app->db->prepare("
                SELECT COUNT(DISTINCT u.id) 
                FROM users u
                JOIN user_roles ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name = 'client'
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting client count: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return 0;
        }
    }
    
    /**
     * Get all users with their roles
     * @return array Users with roles
     */
    private function getAllUsers(): array {
        try {
            $stmt = Application::$app->db->prepare("
                SELECT u.*, GROUP_CONCAT(r.name) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                GROUP BY u.id
                ORDER BY u.id
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting all users: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }
    
    /**
     * Get all available roles
     * @return array All roles
     */
    private function getAllRoles(): array {
        try {
            $stmt = Application::$app->db->prepare("SELECT * FROM roles ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting all roles: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }
    
    /**
     * Validate user edit form data
     * @param array $data Form data
     * @param User $user User object
     * @return array Validation errors
     */
    private function validateUserEdit(array $data, User $user): array {
        $errors = [];
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($data['email'] !== $user->email) {
            // Check if email is already taken by another user
            $existingUser = User::findOne(['email' => $data['email']]);
            if ($existingUser && $existingUser->id !== $user->id) {
                $errors[] = 'Email is already taken';
            }
        }
        
        // Validate full name
        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required';
        }
        
        // Validate password if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters long';
            }
            
            if ($data['password'] !== $data['password_confirm']) {
                $errors[] = 'Passwords do not match';
            }
        }
        
        // Log validation errors
        if (!empty($errors)) {
            Application::$app->logger->warning(
                'Validation errors in user edit form', 
                ['user_id' => $user->id, 'errors' => $errors],
                'users.log'
            );
        }
        
        return $errors;
    }
}
