<?php

namespace App\Models;

use App\Core\Database\DbModel;

/**
 * Модель элемента заказа
 */
class OrderItem extends DbModel
{
    public int $id = 0;
    public int $order_id;
    public int $product_id;
    public int $quantity;
    public float $price;
    public ?string $options = null;
    public string $created_at;

    /**
     * Название таблицы в базе данных
     */
    public static function tableName(): string
    {
        return 'order_items';
    }

    /**
     * Первичный ключ таблицы
     */
    public static function primaryKey(): string
    {
        return 'id';
    }

    /**
     * Атрибуты модели
     */
    public function attributes(): array
    {
        return ['id', 'order_id', 'product_id', 'quantity', 'price', 'options', 'created_at'];
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'order_id' => [self::RULE_REQUIRED],
            'product_id' => [self::RULE_REQUIRED],
            'quantity' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 1]],
            'price' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 0]],
        ];
    }

    /**
     * Получение информации о товаре
     */
    public function getProduct(): ?Product
    {
        return Product::findOne($this->product_id);
    }

    /**
     * Получение форматированной цены
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2, '.', ' ') . ' руб.';
    }

    /**
     * Получение общей стоимости позиции
     */
    public function getTotalPrice(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Получение форматированной общей стоимости
     */
    public function getFormattedTotalPrice(): string
    {
        return number_format($this->getTotalPrice(), 2, '.', ' ') . ' руб.';
    }
}
