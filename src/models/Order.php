<?php

namespace App\Models;

use App\Core\Database\DbModel;

/**
 * Модель заказа
 */
class Order extends DbModel
{
    public int $id = 0;
    public int $user_id;
    public string $status = 'pending';
    public float $total_price = 0.0;
    public string $shipping_address = '';
    public string $payment_method = '';
    public ?string $payment_id = null;
    public ?string $tracking_number = null;
    public string $created_at = '';
    public ?string $updated_at = null;

    /**
     * Название таблицы в базе данных
     */
    public static function tableName(): string
    {
        return 'orders';
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
        return ['id', 'user_id', 'status', 'total_price', 'shipping_address', 'payment_method', 'payment_id', 'tracking_number', 'created_at', 'updated_at'];
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'user_id' => [self::RULE_REQUIRED],
            'total_price' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 0]],
            'shipping_address' => [self::RULE_REQUIRED],
            'payment_method' => [self::RULE_REQUIRED],
        ];
    }

    /**
     * Получение имени пользователя, сделавшего заказ
     */
    public function getUserName(): string
    {
        $user = User::findOne($this->user_id);
        return $user ? $user->name : 'Неизвестный пользователь';
    }

    /**
     * Получение статуса заказа на русском языке
     */
    public function getStatusText(): string
    {
        $statuses = [
            'pending' => 'Ожидает обработки',
            'processing' => 'В обработке',
            'shipped' => 'Отправлен',
            'delivered' => 'Доставлен',
            'canceled' => 'Отменен'
        ];
        
        return $statuses[$this->status] ?? 'Неизвестный статус';
    }

    /**
     * Получение форматированной даты создания заказа
     */
    public function getFormattedDate(): string
    {
        return date('d.m.Y H:i', strtotime($this->created_at));
    }

    /**
     * Получение форматированной суммы заказа
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->total_price, 2, '.', ' ') . ' руб.';
    }

    /**
     * Получение элементов заказа
     */
    public function getItems(): array
    {
        return OrderItem::findAll(['order_id' => $this->id]);
    }

    /**
     * Получение количества товаров в заказе
     */
    public function getItemsCount(): int
    {
        $items = $this->getItems();
        $count = 0;
        
        foreach ($items as $item) {
            $count += $item->quantity;
        }
        
        return $count;
    }
}
