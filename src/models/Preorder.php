<?php

namespace App\Models;

use App\Core\Database\DbModel;

/**
 * u041cu043eu0434u0435u043bu044c u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432
 */
class Preorder extends DbModel
{
    public int $id = 0;
    public int $user_id;
    public int $product_id;
    public int $quantity = 1;
    public ?string $notification_sent = null;
    public string $status = 'waiting'; // waiting, notified, converted, canceled
    public string $created_at;
    public ?string $updated_at = null;

    /**
     * u041du0430u0437u0432u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b u0432 u0431u0430u0437u0435 u0434u0430u043du043du044bu0445
     */
    public static function tableName(): string
    {
        return 'preorders';
    }

    /**
     * u041fu0435u0440u0432u0438u0447u043du044bu0439 u043au043bu044eu0447 u0442u0430u0431u043bu0438u0446u044b
     */
    public static function primaryKey(): string
    {
        return 'id';
    }

    /**
     * u0410u0442u0440u0438u0431u0443u0442u044b u043cu043eu0434u0435u043bu0438
     */
    public function attributes(): array
    {
        return ['id', 'user_id', 'product_id', 'quantity', 'notification_sent', 'status', 'created_at', 'updated_at'];
    }

    /**
     * u041fu0440u0430u0432u0438u043bu0430 u0432u0430u043bu0438u0434u0430u0446u0438u0438
     */
    public function rules(): array
    {
        return [
            'user_id' => [self::RULE_REQUIRED],
            'product_id' => [self::RULE_REQUIRED],
            'quantity' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 1]]
        ];
    }

    /**
     * u041fu043eu043bu0443u0447u0435u043du0438u0435 u0438u043du0444u043eu0440u043cu0430u0446u0438u0438 u043e u0442u043eu0432u0430u0440u0435
     */
    public function getProduct(): ?Product
    {
        return Product::findOne($this->product_id);
    }

    /**
     * u041fu043eu043bu0443u0447u0435u043du0438u0435 u0444u043eu0440u043cu0430u0442u0438u0440u043eu0432u0430u043du043du043eu0439 u0434u0430u0442u044b u0441u043eu0437u0434u0430u043du0438u044f
     */
    public function getFormattedDate(): string
    {
        return date('d.m.Y H:i', strtotime($this->created_at));
    }

    /**
     * u041fu043eu043bu0443u0447u0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430 u043du0430 u0440u0443u0441u0441u043au043eu043c u044fu0437u044bu043au0435
     */
    public function getStatusText(): string
    {
        $statuses = [
            'waiting' => 'u041eu0436u0438u0434u0430u043du0438u0435 u043fu043eu0441u0442u0443u043fu043bu0435u043du0438u044f',
            'notified' => 'u0423u0432u0435u0434u043eu043cu043bu0435u043du0438u0435 u043eu0442u043fu0440u0430u0432u043bu0435u043du043e',
            'converted' => 'u041fu0440u0435u0432u0440u0430u0449u0435u043d u0432 u0437u0430u043au0430u0437',
            'canceled' => 'u041eu0442u043cu0435u043du0435u043d'
        ];
        
        return $statuses[$this->status] ?? 'u041du0435u0438u0437u0432u0435u0441u0442u043du044bu0439 u0441u0442u0430u0442u0443u0441';
    }

    /**
     * u041fu0440u043eu0432u0435u0440u043au0430, u0435u0441u0442u044c u043bu0438 u0442u043eu0432u0430u0440 u0432 u043fu0440u0435u0434u0437u0430u043au0430u0437u0435 u0443 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f
     */
    public static function isPreordered(int $userId, int $productId): bool
    {
        $item = self::findOne([
            'user_id' => $userId,
            'product_id' => $productId,
            'status' => ['waiting', 'notified']
        ]);
        
        return $item !== null;
    }
}
