<?php

namespace App\Models;

use App\Core\Database\DbModel;

/**
 * Модель адреса доставки пользователя
 * 
 * @property int $id
 * @property int $user_id
 * @property string $title Название адреса (например, "Дом", "Работа")
 * @property string $recipient_name Имя получателя
 * @property string $phone Телефон
 * @property string $country Страна
 * @property string $city Город
 * @property string $street Улица
 * @property string $house Номер дома
 * @property string $apartment Квартира/офис
 * @property string $postal_code Почтовый индекс
 * @property int $is_default Флаг основного адреса (1 - основной, 0 - дополнительный)
 * @property string $created_at Дата создания
 * @property string $updated_at Дата обновления
 */
class UserAddress extends DbModel
{
    public int $id = 0;
    public int $user_id = 0;
    public string $title = '';
    public string $recipient_name = '';
    public string $phone = '';
    public string $country = '';
    public string $city = '';
    public string $street = '';
    public string $house = '';
    public string $apartment = '';
    public string $postal_code = '';
    public int $is_default = 0;
    public string $created_at = '';
    public string $updated_at = '';
    
    /**
     * Название таблицы в базе данных
     */
    public static function tableName(): string
    {
        return 'user_addresses';
    }
    
    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'user_id' => [self::RULE_REQUIRED],
            'title' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 50]],
            'recipient_name' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 100]],
            'phone' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 20]],
            'country' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 50]],
            'city' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 100]],
            'street' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 100]],
            'house' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 20]],
            'apartment' => [[self::RULE_MAX, 'max' => 20]],
            'postal_code' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 20]],
            'is_default' => [[self::RULE_IN, 'in' => [0, 1]]]
        ];
    }
    
    /**
     * Атрибуты, которые можно заполнять массово
     */
    public function attributes(): array
    {
        return ['user_id', 'title', 'recipient_name', 'phone', 'country', 'city', 'street', 'house', 'apartment', 'postal_code', 'is_default'];
    }
    
    /**
     * Получение полного адреса в виде строки
     */
    public function getFullAddress(): string
    {
        $parts = [
            $this->country,
            $this->city,
            $this->street,
            'д. ' . $this->house
        ];
        
        if (!empty($this->apartment)) {
            $parts[] = 'кв./офис ' . $this->apartment;
        }
        
        if (!empty($this->postal_code)) {
            array_unshift($parts, $this->postal_code);
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Получение краткого адреса для отображения в списке
     */
    public function getShortAddress(): string
    {
        return $this->city . ', ' . $this->street . ', д. ' . $this->house;
    }
    
    /**
     * Получение информации о получателе
     */
    public function getRecipientInfo(): string
    {
        return $this->recipient_name . ', ' . $this->phone;
    }
    
    /**
     * Обновление всех записей, соответствующих условию
     */
    public static function updateAll(array $attributes, array $condition): bool
    {
        $tableName = static::tableName();
        $db = \App\Core\Application::$app->db;
        
        $params = [];
        $setStatements = [];
        
        foreach ($attributes as $attribute => $value) {
            $setStatements[] = "$attribute = :$attribute";
            $params[":$attribute"] = $value;
        }
        
        $whereConditions = [];
        foreach ($condition as $attribute => $value) {
            if ($attribute === 'ORDER' || $attribute === 'LIMIT') {
                continue;
            }
            $whereConditions[] = "$attribute = :where_$attribute";
            $params[":where_$attribute"] = $value;
        }
        
        $setClause = implode(', ', $setStatements);
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "UPDATE $tableName SET $setClause WHERE $whereClause";
        
        try {
            $statement = $db->prepare($sql);
            return $statement->execute($params);
        } catch (\PDOException $e) {
            \App\Core\Application::$app->logger->error(
                'Error updating records', 
                ['error' => $e->getMessage(), 'sql' => $sql, 'params' => $params],
                'errors.log'
            );
            return false;
        }
    }
    
    /**
     * Действия перед сохранением
     */
    public function beforeSave(bool $isNewRecord): bool
    {
        if ($isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        $this->updated_at = date('Y-m-d H:i:s');
        
        return parent::beforeSave($isNewRecord);
    }
}
