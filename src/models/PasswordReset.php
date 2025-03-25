<?php

namespace App\Models;

use App\Core\Database\DbModel;

class PasswordReset extends DbModel {
    public int $id;
    public int $user_id;
    public string $token;
    public string $expires_at;
    public string $created_at;
    
    /**
     * Get table name for the model
     * @return string
     */
    public static function tableName(): string {
        return 'password_resets';
    }
    
    /**
     * Get primary key field name
     * @return string
     */
    public static function primaryKey(): string {
        return 'id';
    }
    
    /**
     * Get attributes for the model
     * @return array
     */
    public function attributes(): array {
        return ['user_id', 'token', 'expires_at'];
    }
    
    /**
     * Save the model to the database
     * @return bool
     */
    public function save(): bool {
        // Проверка существования таблицы
        $this->createTableIfNotExists();
        
        // Удаление старых токенов для этого пользователя
        $this->deleteOldTokens();
        
        // Получаем атрибуты модели
        $attributes = $this->attributes();
        $params = [];
        
        foreach ($attributes as $attribute) {
            $params[$attribute] = $this->{$attribute};
        }
        
        $tableName = self::tableName();
        $db = \App\Core\Application::$app->db;
        
        // Подготавливаем SQL запрос для вставки данных
        $sql = "INSERT INTO {$tableName} (" . implode(',', $attributes) . ") "
             . "VALUES (" . implode(',', array_map(fn($attr) => ":{$attr}", $attributes)) . ")";        
        
        try {
            $statement = $db->prepare($sql);
            $result = $statement->execute($params);
            
            if ($result) {
                // Если вставка успешна, получаем ID вставленной записи
                $this->id = $db->lastInsertId();
                
                // Логируем успешное создание токена сброса пароля
                \App\Core\Application::$app->logger->info(
                    "Password reset token created for user ID: {$this->user_id}",
                    ['token_id' => $this->id],
                    'users.log'
                );
            } else {
                // Логируем ошибку при создании токена
                \App\Core\Application::$app->logger->error(
                    "Failed to create password reset token for user ID: {$this->user_id}",
                    ['error' => $statement->errorInfo()],
                    'errors.log'
                );
            }
            
            return $result;
        } catch (\PDOException $e) {
            // Логируем исключение при создании токена
            \App\Core\Application::$app->logger->error(
                "Exception when creating password reset token: {$e->getMessage()}",
                ['user_id' => $this->user_id, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return false;
        }
    }
    
    /**
     * Delete the model from the database
     * @return bool
     */
    public function delete(): bool {
        $db = \App\Core\Application::$app->db;
        $statement = $db->prepare("DELETE FROM " . self::tableName() . " WHERE id = :id");
        return $statement->execute(['id' => $this->id]);
    }
    
    /**
     * Create the password_resets table if it doesn't exist
     * @return void
     */
    private function createTableIfNotExists(): void {
        $db = \App\Core\Application::$app->db;
        
        // Проверяем существование таблицы
        $statement = $db->prepare("SHOW TABLES LIKE :table");
        $statement->execute(['table' => self::tableName()]);
        
        // Если таблица существует, удаляем ее (для исправления структуры)
        if ($statement->fetch()) {
            $dropStatement = $db->prepare("DROP TABLE IF EXISTS " . self::tableName());
            $dropStatement->execute();
            
            \App\Core\Application::$app->logger->info(
                'Dropped existing password_resets table for recreation',
                [],
                'users.log'
            );
        }
        
        // Создаем таблицу с правильной структурой
        $createTableStatement = $db->prepare("CREATE TABLE IF NOT EXISTS " . self::tableName() . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (token),
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $createTableStatement->execute();
        
        \App\Core\Application::$app->logger->info(
            'Created password_resets table',
            [],
            'users.log'
        );
    }
    
    /**
     * Delete old tokens for the current user
     * @return void
     */
    private function deleteOldTokens(): void {
        $db = \App\Core\Application::$app->db;
        $statement = $db->prepare("DELETE FROM " . self::tableName() . " WHERE user_id = :user_id");
        $statement->execute(['user_id' => $this->user_id]);
    }
    
    /**
     * Delete expired tokens
     * @return void
     */
    public static function deleteExpiredTokens(): void {
        $db = \App\Core\Application::$app->db;
        $statement = $db->prepare("DELETE FROM " . self::tableName() . " WHERE expires_at < NOW()");
        $statement->execute();
    }
}
