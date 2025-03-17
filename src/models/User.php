<?php

namespace App\Models;

use App\Core\Application;
use PDO;

class User {
    public ?int $id = null;
    public string $email;
    public string $password_hash;
    public ?string $full_name = null;
    public string $status = 'active';
    private array $roles = [];

    public function save(): bool {
        $db = Application::$app->db;
        if ($this->id) {
            // Update
            $statement = $db->prepare("
                UPDATE users 
                SET email = :email, password_hash = :password_hash, 
                    full_name = :full_name, status = :status 
                WHERE id = :id
            ");
            $result = $statement->execute([
                'id' => $this->id,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'full_name' => $this->full_name,
                'status' => $this->status
            ]);
            
            // Debug log
            error_log("User update result: " . ($result ? 'success' : 'failed') . " for user ID: {$this->id}");
            error_log("Updated status to: {$this->status}");
            
            return $result;
        }

        // Insert
        try {
            $db->beginTransaction();

            $statement = $db->prepare("
                INSERT INTO users (email, password_hash, full_name, status)
                VALUES (:email, :password_hash, :full_name, :status)
            ");
            
            $result = $statement->execute([
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'full_name' => $this->full_name,
                'status' => $this->status
            ]);

            if ($result) {
                $this->id = $db->lastInsertId();
            }

            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
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
        error_log('Setting roles for user ID ' . $this->id . ': ' . implode(', ', $roleIds));
        
        try {
            $db->beginTransaction();

            // Delete existing roles
            $statement = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $statement->execute(['user_id' => $this->id]);
            error_log('Deleted existing roles for user ID: ' . $this->id);

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
                error_log('Added role ID ' . $roleId . ' for user ID ' . $this->id . ': ' . ($result ? 'success' : 'failed'));
            }

            $db->commit();
            error_log('Committed role changes for user ID: ' . $this->id);
            
            // Clear the cached roles so they'll be reloaded on next getRoles() call
            $this->roles = [];
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Error setting roles for user ID ' . $this->id . ': ' . $e->getMessage());
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
            error_log('Retrieved roles for user ID ' . $this->id . ': ' . print_r($this->roles, true));
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
        error_log('Cleared roles cache for user ID: ' . $this->id);
    }
}
