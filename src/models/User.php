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
            return $statement->execute([
                'id' => $this->id,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'full_name' => $this->full_name,
                'status' => $this->status
            ]);
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

    public function setRoles(array $roles): void {
        $db = Application::$app->db;
        try {
            $db->beginTransaction();

            // Delete existing roles
            $statement = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $statement->execute(['user_id' => $this->id]);

            // Insert new roles
            foreach ($roles as $roleId) {
                $statement = $db->prepare("
                    INSERT INTO user_roles (user_id, role_id)
                    VALUES (:user_id, :role_id)
                ");
                $statement->execute([
                    'user_id' => $this->id,
                    'role_id' => $roleId
                ]);
            }

            $db->commit();
            $this->roles = $roles;
        } catch (\Exception $e) {
            $db->rollBack();
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
        return $statement->fetchObject(static::class);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
    }

    public function setPassword(string $password): void {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
