<?php

namespace App\Models;

use App\Core\Application;
use App\Core\Database\DbModel;
use PDO;

class User extends DbModel {
    public ?int $id = null;
    public string $email = '';
    public string $password_hash = '';
    public string $full_name = '';
    public string $status = 'active';
    public ?string $phone = null;
    public bool $notification_order = true;
    public bool $notification_promo = false;
    public bool $notification_system = true;
    public ?string $avatar = null;
    
    private array $roles = [];
    
    /**
     * Возвращает имя таблицы в базе данных
     * @return string
     */
    public static function tableName(): string {
        return 'users';
    }

    public function save(): bool {
        $db = Application::$app->db;
        if ($this->id) {
            // Обновление существующего пользователя
            Application::$app->logger->info("User::save() - Обновление пользователя ID: {$this->id}", ['email' => $this->email], 'users.log');
            Application::$app->logger->info("User::save() - Статус перед обновлением: {$this->status}", ['user_id' => $this->id], 'status.log');
            
            // Проверка текущего статуса в базе данных
            $checkStmt = $db->prepare("SELECT status FROM users WHERE id = :id");
            $checkStmt->execute(['id' => $this->id]);
            $currentStatus = $checkStmt->fetchColumn();
            Application::$app->logger->info("User::save() - Текущий статус в базе данных: {$currentStatus}", ['user_id' => $this->id], 'status.log');
            
            // Update
            $statement = $db->prepare("
                UPDATE users 
                SET email = :email, password_hash = :password_hash, 
                    full_name = :full_name, avatar = :avatar,
                    phone = :phone, notification_order = :notification_order,
                    notification_promo = :notification_promo, notification_system = :notification_system
                WHERE id = :id
            ");
            
            // Убедимся, что статус имеет допустимое значение
            if (!in_array($this->status, ['active', 'pending', 'suspended'])) {
                Application::$app->logger->warning("User::save() - Недопустимый статус: {$this->status}, устанавливаем 'active'", ['user_id' => $this->id], 'status.log');
                $this->status = 'active';
            } else {
                Application::$app->logger->info("User::save() - Статус прошел валидацию: {$this->status}", ['user_id' => $this->id], 'status.log');
            }
            
            // Обновление статуса отдельно
            $statusUpdateStmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            $statusResult = $statusUpdateStmt->execute([
                'id' => $this->id,
                'status' => $this->status
            ]);
            
            Application::$app->logger->info("User::save() - Результат обновления статуса: " . ($statusResult ? 'успешно' : 'ошибка'), ['user_id' => $this->id], 'status.log');
            
            $params = [
                'id' => $this->id,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'full_name' => $this->full_name,
                'avatar' => $this->avatar,
                'phone' => $this->phone,
                'notification_order' => $this->notification_order ? 1 : 0,
                'notification_promo' => $this->notification_promo ? 1 : 0,
                'notification_system' => $this->notification_system ? 1 : 0
            ];
            
            Application::$app->logger->debug("User::save() - Параметры запроса: " . json_encode($params), ['user_id' => $this->id], 'users.log');
            $result = $statement->execute($params);
            
            // Debug log
            Application::$app->logger->info("User::save() - Результат обновления: " . ($result ? 'успешно' : 'ошибка') . " для пользователя ID: {$this->id}", ['user_id' => $this->id], 'users.log');
            
            // Проверка статуса после обновления
            $checkStmt = $db->prepare("SELECT status FROM users WHERE id = :id");
            $checkStmt->execute(['id' => $this->id]);
            $finalStatus = $checkStmt->fetchColumn();
            Application::$app->logger->info("User::save() - Статус после обновления в базе данных: {$finalStatus}", ['user_id' => $this->id], 'status.log');
            
            return $result && $statusResult;
        }

        // Insert
        try {
            $db->beginTransaction();

            // Убедимся, что статус имеет допустимое значение
            if (!in_array($this->status, ['active', 'pending', 'suspended'])) {
                Application::$app->logger->warning("User::save() - Недопустимый статус: {$this->status}, устанавливаем 'active'", ['email' => $this->email], 'status.log');
                $this->status = 'active';
            }

            $statement = $db->prepare("
                INSERT INTO users (email, password_hash, full_name, status, avatar, phone, notification_order, notification_promo, notification_system)
                VALUES (:email, :password_hash, :full_name, :status, :avatar, :phone, :notification_order, :notification_promo, :notification_system)
            ");

            $params = [
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'full_name' => $this->full_name,
                'status' => $this->status,
                'avatar' => $this->avatar,
                'phone' => $this->phone,
                'notification_order' => $this->notification_order ? 1 : 0,
                'notification_promo' => $this->notification_promo ? 1 : 0,
                'notification_system' => $this->notification_system ? 1 : 0
            ];

            Application::$app->logger->debug("User::save() - Параметры запроса: " . json_encode($params), ['email' => $this->email], 'users.log');
            $result = $statement->execute($params);

            if ($result) {
                $this->id = (int)$db->lastInsertId();
                Application::$app->logger->info("User::save() - Пользователь успешно создан с ID: {$this->id}", ['email' => $this->email], 'users.log');
            } else {
                Application::$app->logger->error("User::save() - Ошибка при создании пользователя", ['email' => $this->email], 'users.log');
            }

            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            Application::$app->logger->error("User::save() - Ошибка при создании пользователя: " . $e->getMessage(), ['email' => $this->email], 'users.log');
            throw $e;
        }
    }

    public function setRoles(array $roleIds): void {
        $db = Application::$app->db;
        
        // Ensure at least one role is selected (default to Client role if none)
        if (empty($roleIds)) {
            $roleIds = [3]; // Default to Client role (ID: 3)
        }
        
        // Ensure all role IDs are integers
        $roleIds = array_map(function($id) {
            return (int)$id;
        }, $roleIds);
        
        // Debug log
        Application::$app->logger->debug('Setting roles for user ID ' . $this->id . ': ' . implode(', ', $roleIds), ['user_id' => $this->id], 'users.log');
        
        try {
            $db->beginTransaction();

            // Check if user had seller role before the update
            // Пройдемся к базе данных для проверки роли продавца
            $sellerRoleId = 2; // ID роли продавца
            $checkRoleStmt = $db->prepare("SELECT 1 FROM user_roles WHERE user_id = :user_id AND role_id = :role_id");
            $checkRoleStmt->execute([
                'user_id' => $this->id,
                'role_id' => $sellerRoleId
            ]);
            $hadSellerRole = (bool)$checkRoleStmt->fetchColumn();
            
            Application::$app->logger->info('User ID ' . $this->id . ' had seller role before update: ' . ($hadSellerRole ? 'yes' : 'no'), ['user_id' => $this->id], 'users.log');
            
            // Delete existing roles
            $statement = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $statement->execute(['user_id' => $this->id]);
            Application::$app->logger->info('Deleted existing roles for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');

            // Insert new roles
            foreach ($roleIds as $roleId) {
                $statement = $db->prepare("
                    INSERT INTO user_roles (user_id, role_id)
                    VALUES (:user_id, :role_id)
                ");
                $result = $statement->execute([
                    'user_id' => $this->id,
                    'role_id' => $roleId
                ]);
                Application::$app->logger->info('Added role ID ' . $roleId . ' for user ID ' . $this->id . ': ' . ($result ? 'success' : 'failed'), ['user_id' => $this->id], 'users.log');
            }
            
            // Clear the cached roles so they'll be reloaded on next getRoles() call
            $this->roles = [];
            
            // Check if user has seller role after the update
            // Вместо использования hasRole, который может использовать кэшированные данные,
            // проверяем напрямую, есть ли роль продавца в новом списке ролей
            $hasSellerRole = false;
            $sellerRoleId = 2; // Assuming seller role ID is 2
            if (in_array($sellerRoleId, $roleIds)) {
                $hasSellerRole = true;
            }
            
            // If user was given seller role and didn't have it before, create seller profile
            if ($hasSellerRole && !$hadSellerRole) {
                Application::$app->logger->info('User ID ' . $this->id . ' was given seller role, creating seller profile', ['user_id' => $this->id], 'users.log');
                
                // Check if seller profile already exists
                $checkStmt = $db->prepare("SELECT id FROM seller_profiles WHERE user_id = :user_id");
                $checkStmt->execute(['user_id' => $this->id]);
                $existingProfile = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existingProfile) {
                    // Create seller profile with all necessary fields
                    $createStmt = $db->prepare("
                        INSERT INTO seller_profiles (
                            user_id, seller_type, min_order_amount, 
                            name, description, email, phone, avatar_url
                        )
                        VALUES (
                            :user_id, 'ordinary', 0, 
                            :name, :description, :email, :phone, ''
                        )
                    ");
                    
                    // Use user's full name and email for initial seller profile
                    $result = $createStmt->execute([
                        'user_id' => $this->id,
                        'name' => $this->full_name . "'s Shop",
                        'description' => 'Welcome to my shop!',
                        'email' => $this->email,
                        'phone' => ''
                    ]);
                    
                    if ($result) {
                        Application::$app->logger->info('Created seller profile for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');
                    } else {
                        Application::$app->logger->error('Failed to create seller profile for user ID: ' . $this->id, ['user_id' => $this->id], 'errors.log');
                    }
                } else {
                    Application::$app->logger->info('Seller profile already exists for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');
                }
            }
            // If user lost seller role, handle accordingly (optionally delete seller profile)
            else if (!$hasSellerRole && $hadSellerRole) {
                Application::$app->logger->info('User ID ' . $this->id . ' lost seller role, deleting seller profile', ['user_id' => $this->id], 'users.log');
                
                // Delete seller profile
                $deleteStmt = $db->prepare("DELETE FROM seller_profiles WHERE user_id = :user_id");
                $result = $deleteStmt->execute(['user_id' => $this->id]);
                
                if ($result) {
                    Application::$app->logger->info('Deleted seller profile for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');
                } else {
                    Application::$app->logger->error('Failed to delete seller profile for user ID: ' . $this->id, ['user_id' => $this->id], 'errors.log');
                }
            }

            $db->commit();
            Application::$app->logger->info('Committed role changes for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');
        } catch (\Exception $e) {
            $db->rollBack();
            Application::$app->logger->error('Error setting roles for user ID ' . $this->id . ': ' . $e->getMessage(), ['user_id' => $this->id], 'users.log');
            throw $e;
        }
    }

    public function getRoles(): array {
        if (empty($this->roles) && $this->id) {
            $statement = Application::$app->db->prepare("
                SELECT r.* FROM roles r
                JOIN user_roles ur ON ur.role_id = r.id
                WHERE ur.user_id = :user_id
            ");
            $statement->execute(['user_id' => $this->id]);
            $this->roles = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure role IDs are integers
            foreach ($this->roles as &$role) {
                $role['id'] = (int)$role['id'];
            }
            
            // Debug log
            Application::$app->logger->debug('Retrieved roles for user ID ' . $this->id . ': ' . print_r($this->roles, true), ['user_id' => $this->id], 'users.log');
        }
        return $this->roles;
    }

    public function hasRole(string $roleName): bool {
        $roles = $this->getRoles();
        return in_array($roleName, array_column($roles, 'name'));
    }

    public static function findOne($where): ?User {
        $tableName = 'users';
        $attributes = array_keys($where);
        $sql = implode("AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
        $statement = Application::$app->db->prepare("SELECT * FROM $tableName WHERE $sql");
        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }
        $statement->execute();
        $result = $statement->fetchObject(static::class);
        
        // Return null instead of false when no record is found
        return $result === false ? null : $result;
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
    }

    public function setPassword(string $password): void {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Clear the cached roles to force a fresh load from the database
     */
    public function clearRolesCache(): void {
        $this->roles = [];
        Application::$app->logger->debug('Cleared roles cache for user ID: ' . $this->id, ['user_id' => $this->id], 'users.log');
    }
}
