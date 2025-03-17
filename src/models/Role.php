<?php

namespace App\Models;

use App\Core\Application;
use PDO;

class Role {
    public ?int $id = null;
    public string $name;

    public static function findAll(): array {
        $statement = Application::$app->db->prepare("SELECT * FROM roles");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    public static function findByName(string $name): ?Role {
        $statement = Application::$app->db->prepare("SELECT * FROM roles WHERE name = :name");
        $statement->execute(['name' => $name]);
        return $statement->fetchObject(static::class);
    }

    public function save(): bool {
        $db = Application::$app->db;
        if ($this->id) {
            $statement = $db->prepare("UPDATE roles SET name = :name WHERE id = :id");
            return $statement->execute([
                'id' => $this->id,
                'name' => $this->name
            ]);
        }

        $statement = $db->prepare("INSERT INTO roles (name) VALUES (:name)");
        $result = $statement->execute(['name' => $this->name]);
        if ($result) {
            $this->id = $db->lastInsertId();
        }
        return $result;
    }

    public function getUsers(): array {
        $statement = Application::$app->db->prepare("
            SELECT u.* FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            WHERE ur.role_id = :role_id
        ");
        $statement->execute(['role_id' => $this->id]);
        return $statement->fetchAll(PDO::FETCH_CLASS, User::class);
    }
}
