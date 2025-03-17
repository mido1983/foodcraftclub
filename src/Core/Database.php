<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database {
    private PDO $pdo;
    private static array $instances = [];

    public function __construct() {
        try {
            $this->pdo = new PDO(
                $_ENV['DB_DSN'],
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get a PDO attribute
     * 
     * @param int $attribute The attribute to get (PDO::ATTR_* constant)
     * @return mixed The attribute value
     */
    public function getAttribute(int $attribute) {
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     * 
     * @param string|null $name Name of the sequence object from which the ID should be returned
     * @return string The last insert ID
     */
    public function lastInsertId(?string $name = null): string {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * 
     * @param string $sql SQL statement to prepare
     * @return PDOStatement The prepared statement
     */
    public function prepare(string $sql): PDOStatement {
        return $this->pdo->prepare($sql);
    }

    public function applyMigrations() {
        // Create migrations table if not exists
        $this->createMigrationsTable();

        // Get applied migrations
        $appliedMigrations = $this->getAppliedMigrations();

        // Get all migration files
        $files = scandir(Application::$app->rootPath . '/database/migrations');
        $toApplyMigrations = array_diff($files, ['.', '..', '.gitkeep']);

        // Get pending migrations
        $newMigrations = [];
        foreach ($toApplyMigrations as $migration) {
            if (pathinfo($migration, PATHINFO_EXTENSION) === 'php' && !in_array($migration, $appliedMigrations)) {
                $newMigrations[] = $migration;
            }
        }

        if (empty($newMigrations)) {
            $this->log("All migrations are applied");
            return;
        }

        // Sort migrations by filename
        sort($newMigrations);

        // Apply new migrations
        foreach ($newMigrations as $migration) {
            require_once Application::$app->rootPath . '/database/migrations/' . $migration;
            $className = pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $className();
            
            $this->log("Applying migration $migration");
            $instance->up();
            
            // Save migration to database
            $this->saveMigration($migration);
            $this->log("Applied migration $migration");
        }
    }

    protected function createMigrationsTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    protected function getAppliedMigrations() {
        $statement = $this->pdo->prepare("SELECT migration FROM migrations");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function saveMigration($migration) {
        $statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $statement->execute(['migration' => $migration]);
    }

    /**
     * Begin a transaction
     * 
     * @return bool TRUE on success or FALSE on failure
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool TRUE on success or FALSE on failure
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }

    /**
     * Roll back a transaction
     * 
     * @return bool TRUE on success or FALSE on failure
     */
    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }

    protected function log($message) {
        echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
    }
}
