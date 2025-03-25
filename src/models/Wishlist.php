<?php

namespace App\Models;

use App\Core\Database\DbModel;

/**
 * u041cu043eu0434u0435u043bu044c u0438u0437u0431u0440u0430u043du043du044bu0445 u0442u043eu0432u0430u0440u043eu0432
 */
class Wishlist extends DbModel
{
    public int $id = 0;
    public int $user_id;
    public int $product_id;
    public string $created_at;

    /**
     * u041du0430u0437u0432u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b u0432 u0431u0430u0437u0435 u0434u0430u043du043du044bu0445
     */
    public static function tableName(): string
    {
        return 'wishlists';
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
        return ['id', 'user_id', 'product_id', 'created_at'];
    }

    /**
     * u041fu0440u0430u0432u0438u043bu0430 u0432u0430u043bu0438u0434u0430u0446u0438u0438
     */
    public function rules(): array
    {
        return [
            'user_id' => [self::RULE_REQUIRED],
            'product_id' => [self::RULE_REQUIRED]
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
     * u041fu0440u043eu0432u0435u0440u043au0430, u0435u0441u0442u044c u043bu0438 u0442u043eu0432u0430u0440 u0432 u0438u0437u0431u0440u0430u043du043du043eu043c u0443 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f
     */
    public static function isInWishlist(int $userId, int $productId): bool
    {
        $item = self::findOne([
            'user_id' => $userId,
            'product_id' => $productId
        ]);
        
        return $item !== null;
    }
}
