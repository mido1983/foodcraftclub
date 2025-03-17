<?php

namespace App\Core\Database;

use App\Core\Application;
use PDO;

/**
 * Базовый класс для моделей, работающих с базой данных
 */
abstract class DbModel {
    /**
     * Имя таблицы в базе данных
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Первичный ключ таблицы
     * @return string
     */
    public static function primaryKey(): string {
        return 'id';
    }

    /**
     * Получить запись по первичному ключу
     * @param mixed $id Значение первичного ключа
     * @return static|null
     */
    public static function findOne($id): ?self {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();
        $statement = Application::$app->db->prepare("SELECT * FROM {$tableName} WHERE {$primaryKey} = :id");
        $statement->execute(['id' => $id]);
        $data = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $model = new static();
        foreach ($data as $key => $value) {
            if (property_exists($model, $key)) {
                $model->{$key} = $value;
            }
        }
        
        return $model;
    }

    /**
     * Найти запись по условию
     * @param array $where Условия поиска в формате ['поле' => 'значение']
     * @return static|null
     */
    public static function findOneBy(array $where): ?self {
        $tableName = static::tableName();
        $attributes = array_keys($where);
        $sql = implode(" AND ", array_map(fn($attr) => "{$attr} = :{$attr}", $attributes));
        $statement = Application::$app->db->prepare("SELECT * FROM {$tableName} WHERE {$sql}");
        $statement->execute($where);
        $data = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $model = new static();
        foreach ($data as $key => $value) {
            if (property_exists($model, $key)) {
                $model->{$key} = $value;
            }
        }
        
        return $model;
    }

    /**
     * Найти все записи по условию
     * @param array $where Условия поиска в формате ['поле' => 'значение']
     * @return static[]
     */
    public static function findAllBy(array $where = []): array {
        $tableName = static::tableName();
        $sql = "SELECT * FROM {$tableName}";
        $params = [];
        
        if (!empty($where)) {
            $attributes = array_keys($where);
            $sql .= " WHERE " . implode(" AND ", array_map(fn($attr) => "{$attr} = :{$attr}", $attributes));
            $params = $where;
        }
        
        $statement = Application::$app->db->prepare($sql);
        $statement->execute($params);
        $data = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $models = [];
        foreach ($data as $item) {
            $model = new static();
            foreach ($item as $key => $value) {
                if (property_exists($model, $key)) {
                    $model->{$key} = $value;
                }
            }
            $models[] = $model;
        }
        
        return $models;
    }

    /**
     * Найти все записи
     * @return static[]
     */
    public static function findAll(): array {
        return static::findAllBy([]);
    }
}
