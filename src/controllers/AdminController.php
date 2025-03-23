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
                                
                                // Автоматически создаем или удаляем профиль продавца при изменении роли
                                if ($roleName === 'seller') {
                                    // Проверяем, существует ли профиль продавца
                                    $profileStmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = :user_id");
                                    $profileStmt->execute(['user_id' => $user->id]);
                                    $hasSellerProfile = (bool)$profileStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    // Если профиля продавца нет, создаем его
                                    if (!$hasSellerProfile) {
                                        $createStmt = $db->prepare("
                                            INSERT INTO seller_profiles (user_id, seller_type, min_order_amount)
                                            VALUES (:user_id, 'ordinary', 0)
                                        ");
                                        $result = $createStmt->execute(['user_id' => $user->id]);
                                        
                                        if ($result) {
                                            Application::$app->logger->info(
                                                'Профиль продавца автоматически создан при смене роли', 
                                                ['user_id' => $user->id],
                                                'users.log'
                                            );
                                        } else {
                                            Application::$app->logger->error(
                                                'Ошибка при автоматическом создании профиля продавца', 
                                                ['user_id' => $user->id],
                                                'errors.log'
                                            );
                                        }
                                    }
                                } elseif ($roleName !== 'seller') {
                                    // Проверяем, существует ли профиль продавца
                                    $profileStmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = :user_id");
                                    $profileStmt->execute(['user_id' => $user->id]);
                                    $hasSellerProfile = (bool)$profileStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    // Если профиль продавца есть, удаляем его
                                    if ($hasSellerProfile) {
                                        $deleteStmt = $db->prepare("DELETE FROM seller_profiles WHERE user_id = :user_id");
                                        $result = $deleteStmt->execute(['user_id' => $user->id]);
                                        
                                        if ($result) {
                                            Application::$app->logger->info(
                                                'Профиль продавца автоматически удален при смене роли', 
                                                ['user_id' => $user->id],
                                                'users.log'
                                            );
                                        } else {
                                            Application::$app->logger->error(
                                                'Ошибка при автоматическом удалении профиля продавца', 
                                                ['user_id' => $user->id],
                                                'errors.log'
                                            );
                                        }
                                    }
                                }
                                
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
     * Создать или удалить профиль продавца для пользователя
     * @param int $id ID пользователя
     * @return string Редирект на страницу пользователей
     */
    public function manageSellerProfile(int $id): string {
        try {
            $db = Application::$app->db;
            
            // Проверяем, существует ли пользователь
            $userStmt = $db->prepare("SELECT id, email, full_name FROM users WHERE id = :id");
            $userStmt->execute(['id' => $id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                Application::$app->session->setFlash('error', 'Пользователь не найден');
                return Application::$app->response->redirect('/admin/users');
            }
            
            // Проверяем, есть ли у пользователя роль продавца
            $rolesStmt = $db->prepare("
                SELECT r.* FROM roles r
                JOIN user_roles ur ON ur.role_id = r.id
                WHERE ur.user_id = :user_id AND r.name = 'seller'
            ");
            $rolesStmt->execute(['user_id' => $id]);
            $hasSellerRole = (bool)$rolesStmt->fetch(PDO::FETCH_ASSOC);
            
            // Проверяем, существует ли профиль продавца
            $profileStmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = :user_id");
            $profileStmt->execute(['user_id' => $id]);
            $hasSellerProfile = (bool)$profileStmt->fetch(PDO::FETCH_ASSOC);
            
            // Логируем текущее состояние
            Application::$app->logger->info(
                'Управление профилем продавца', 
                [
                    'user_id' => $id, 
                    'has_seller_role' => $hasSellerRole ? 'yes' : 'no',
                    'has_seller_profile' => $hasSellerProfile ? 'yes' : 'no'
                ],
                'users.log'
            );
            
            // Если у пользователя есть роль продавца, но нет профиля - создаем профиль
            if ($hasSellerRole && !$hasSellerProfile) {
                $createStmt = $db->prepare("
                    INSERT INTO seller_profiles (user_id, seller_type, min_order_amount)
                    VALUES (:user_id, 'ordinary', 0)
                ");
                $result = $createStmt->execute(['user_id' => $id]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'Профиль продавца успешно создан');
                    Application::$app->logger->info(
                        'Профиль продавца создан', 
                        ['user_id' => $id],
                        'users.log'
                    );
                } else {
                    Application::$app->session->setFlash('error', 'Ошибка при создании профиля продавца');
                    Application::$app->logger->error(
                        'Ошибка при создании профиля продавца', 
                        ['user_id' => $id],
                        'errors.log'
                    );
                }
            }
            // Если у пользователя нет роли продавца, но есть профиль - удаляем профиль
            elseif (!$hasSellerRole && $hasSellerProfile) {
                $deleteStmt = $db->prepare("DELETE FROM seller_profiles WHERE user_id = :user_id");
                $result = $deleteStmt->execute(['user_id' => $id]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'Профиль продавца успешно удален');
                    Application::$app->logger->info(
                        'Профиль продавца удален', 
                        ['user_id' => $id],
                        'users.log'
                    );
                } else {
                    Application::$app->session->setFlash('error', 'Ошибка при удалении профиля продавца');
                    Application::$app->logger->error(
                        'Ошибка при удалении профиля продавца', 
                        ['user_id' => $id],
                        'errors.log'
                    );
                }
            }
            // Если состояние уже корректное
            else {
                if ($hasSellerRole && $hasSellerProfile) {
                    Application::$app->session->setFlash('info', 'Профиль продавца уже существует');
                } else {
                    Application::$app->session->setFlash('info', 'Пользователь не является продавцом');
                }
            }
            
            return Application::$app->response->redirect('/admin/users/edit/' . $id);
            
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Ошибка при управлении профилем продавца: ' . $e->getMessage(), 
                ['user_id' => $id, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'Произошла ошибка: ' . $e->getMessage());
            return Application::$app->response->redirect('/admin/users');
        }
    }
    
    /**
     * Display cities and districts management page
     * @return string Rendered view
     */
    public function deliveryZones(): string {
        $this->view->title = 'Manage Delivery Zones';
        
        try {
            // Get all districts
            $districtsStmt = Application::$app->db->prepare("SELECT id, district_name FROM districts ORDER BY district_name ASC");
            $districtsStmt->execute();
            $districts = $districtsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all cities with district names
            $citiesStmt = Application::$app->db->prepare("
                SELECT c.id, c.city_name, c.district_id, d.district_name 
                FROM cities c
                INNER JOIN districts d ON c.district_id = d.id
                ORDER BY d.district_name ASC, c.city_name ASC
            ");
            $citiesStmt->execute();
            $cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->render('admin/delivery-zones', [
                'cities' => $cities,
                'districts' => $districts
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error loading delivery zones data', 
                ['error' => $e->getMessage()],
                'admin.log'
            );
            
            Application::$app->session->setFlash('error', 'Error loading delivery zones data: ' . $e->getMessage());
            return $this->render('admin/delivery-zones', [
                'cities' => [],
                'districts' => []
            ]);
        }
    }
    
    /**
     * Add new city
     * @return string Redirect response
     */
    public function addCity(): string {
        if (Application::$app->request->isPost()) {
            $cityName = Application::$app->request->getBody()['city_name'] ?? '';
            $districtId = (int)Application::$app->request->getBody()['district_id'] ?? 0;
            
            if (empty($cityName)) {
                Application::$app->session->setFlash('error', 'City name is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            if ($districtId <= 0) {
                Application::$app->session->setFlash('error', 'Valid district is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if city already exists
                $checkStmt = $db->prepare("SELECT id FROM cities WHERE city_name = :city_name");
                $checkStmt->execute(['city_name' => $cityName]);
                
                if ($checkStmt->rowCount() > 0) {
                    Application::$app->session->setFlash('error', 'City with this name already exists');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Add new city
                $insertStmt = $db->prepare("INSERT INTO cities (city_name, district_id) VALUES (:city_name, :district_id)");
                $result = $insertStmt->execute([
                    'city_name' => $cityName,
                    'district_id' => $districtId
                ]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'City added successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to add city');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error adding city', 
                    ['city_name' => $cityName, 'district_id' => $districtId, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error adding city: ' . $e->getMessage());
            }
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
    }
    
    /**
     * Edit city
     * @return string Redirect response
     */
    public function editCity(): string {
        if (Application::$app->request->isPost()) {
            $cityId = (int)Application::$app->request->getBody()['city_id'] ?? 0;
            $cityName = Application::$app->request->getBody()['city_name'] ?? '';
            $districtId = (int)Application::$app->request->getBody()['district_id'] ?? 0;
            
            if (empty($cityId)) {
                Application::$app->session->setFlash('error', 'City ID is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            if (empty($cityName)) {
                Application::$app->session->setFlash('error', 'City name is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            if ($districtId <= 0) {
                Application::$app->session->setFlash('error', 'Valid district is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if city exists
                $checkStmt = $db->prepare("SELECT id FROM cities WHERE id = :id");
                $checkStmt->execute(['id' => $cityId]);
                
                if ($checkStmt->rowCount() === 0) {
                    Application::$app->session->setFlash('error', 'City not found');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Check if new name already exists for another city
                $duplicateStmt = $db->prepare("SELECT id FROM cities WHERE city_name = :city_name AND id != :id");
                $duplicateStmt->execute(['city_name' => $cityName, 'id' => $cityId]);
                
                if ($duplicateStmt->rowCount() > 0) {
                    Application::$app->session->setFlash('error', 'Another city with this name already exists');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Update city
                $updateStmt = $db->prepare("UPDATE cities SET city_name = :city_name, district_id = :district_id WHERE id = :id");
                $result = $updateStmt->execute([
                    'city_name' => $cityName, 
                    'district_id' => $districtId,
                    'id' => $cityId
                ]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'City updated successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to update city');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error updating city', 
                    ['city_id' => $cityId, 'city_name' => $cityName, 'district_id' => $districtId, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error updating city: ' . $e->getMessage());
            }
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
    }
    
    /**
     * Delete city
     * @return string Redirect response
     */
    public function deleteCity(): string {
        if (Application::$app->request->isPost()) {
            $cityId = (int)Application::$app->request->getBody()['city_id'] ?? 0;
            
            if (empty($cityId)) {
                Application::$app->session->setFlash('error', 'City ID is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if city exists
                $checkStmt = $db->prepare("SELECT id FROM cities WHERE id = :id");
                $checkStmt->execute(['id' => $cityId]);
                
                if ($checkStmt->rowCount() === 0) {
                    Application::$app->session->setFlash('error', 'City not found');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Delete city
                $deleteStmt = $db->prepare("DELETE FROM cities WHERE id = :id");
                $result = $deleteStmt->execute(['id' => $cityId]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'City deleted successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to delete city');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error deleting city', 
                    ['city_id' => $cityId, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error deleting city: ' . $e->getMessage());
            }
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
    }
    
    /**
     * Add new district
     * @return string Redirect response
     */
    public function addDistrict(): string {
        if (Application::$app->request->isPost()) {
            $districtName = Application::$app->request->getBody()['district_name'] ?? '';
            
            if (empty($districtName)) {
                Application::$app->session->setFlash('error', 'District name is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if district already exists
                $checkDistrictStmt = $db->prepare("SELECT id FROM districts WHERE district_name = :district_name");
                $checkDistrictStmt->execute(['district_name' => $districtName]);
                
                if ($checkDistrictStmt->rowCount() > 0) {
                    Application::$app->session->setFlash('error', 'District already exists');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Add new district
                $insertStmt = $db->prepare("INSERT INTO districts (district_name) VALUES (:district_name)");
                $result = $insertStmt->execute(['district_name' => $districtName]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'District added successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to add district');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error adding district', 
                    ['district_name' => $districtName, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error adding district: ' . $e->getMessage());
            }
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
    }
    
    /**
     * Edit district
     * @return string Redirect response
     */
    public function editDistrict(): string {
        if (Application::$app->request->isPost()) {
            $districtId = (int)Application::$app->request->getBody()['district_id'] ?? 0;
            $districtName = Application::$app->request->getBody()['district_name'] ?? '';
            
            if (empty($districtId)) {
                Application::$app->session->setFlash('error', 'District ID is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            if (empty($districtName)) {
                Application::$app->session->setFlash('error', 'District name is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if district exists
                $checkStmt = $db->prepare("SELECT id FROM districts WHERE id = :id");
                $checkStmt->execute(['id' => $districtId]);
                
                if ($checkStmt->rowCount() === 0) {
                    Application::$app->session->setFlash('error', 'District not found');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Check if new name already exists for another district
                $duplicateStmt = $db->prepare("
                    SELECT id FROM districts 
                    WHERE district_name = :district_name AND id != :id
                ");
                $duplicateStmt->execute([
                    'district_name' => $districtName, 
                    'id' => $districtId
                ]);
                
                if ($duplicateStmt->rowCount() > 0) {
                    Application::$app->session->setFlash('error', 'District name already exists');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Update district
                $updateStmt = $db->prepare("UPDATE districts SET district_name = :district_name WHERE id = :id");
                $result = $updateStmt->execute([
                    'district_name' => $districtName, 
                    'id' => $districtId
                ]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'District updated successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to update district');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error updating district', 
                    ['district_id' => $districtId, 'district_name' => $districtName, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error updating district: ' . $e->getMessage());
            }
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
    }
    
    /**
     * Delete district
     * @return string Redirect response
     */
    public function deleteDistrict(): string {
        if (Application::$app->request->isPost()) {
            $districtId = (int)Application::$app->request->getBody()['district_id'] ?? 0;
            
            if (empty($districtId)) {
                Application::$app->session->setFlash('error', 'District ID is required');
                return Application::$app->response->redirect('/admin/delivery-zones');
            }
            
            try {
                $db = Application::$app->db;
                
                // Check if district exists
                $checkStmt = $db->prepare("SELECT id FROM districts WHERE id = :id");
                $checkStmt->execute(['id' => $districtId]);
                
                if ($checkStmt->rowCount() === 0) {
                    Application::$app->session->setFlash('error', 'District not found');
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Check if district is being used by cities
                $checkUsageStmt = $db->prepare("SELECT COUNT(*) as count FROM cities WHERE district_id = :district_id");
                $checkUsageStmt->execute(['district_id' => $districtId]);
                $usageCount = (int)$checkUsageStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($usageCount > 0) {
                    Application::$app->session->setFlash('error', "Cannot delete district: it is used by {$usageCount} cities");
                    return Application::$app->response->redirect('/admin/delivery-zones');
                }
                
                // Delete district
                $deleteStmt = $db->prepare("DELETE FROM districts WHERE id = :id");
                $result = $deleteStmt->execute(['id' => $districtId]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'District deleted successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to delete district');
                }
            } catch (Exception $e) {
                Application::$app->logger->error(
                    'Error deleting district', 
                    ['district_id' => $districtId, 'error' => $e->getMessage()],
                    'admin.log'
                );
                
                Application::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            }
            
            return Application::$app->response->redirect('/admin/delivery-zones');
        }
        
        return Application::$app->response->redirect('/admin/delivery-zones');
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
