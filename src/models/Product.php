<?php

namespace App\Models;

use App\Core\Application;
use PDO;

class Product {
    public ?int $id = null;
    public int $seller_profile_id;
    public string $product_name;
    public ?string $description = null;
    public float $price;
    public bool $is_active = true;
    public bool $available_for_preorder = false;
    private array $images = [];

    public function save(): bool {
        $db = Application::$app->db;
        try {
            $db->beginTransaction();

            if ($this->id) {
                $statement = $db->prepare("
                    UPDATE products 
                    SET seller_profile_id = :seller_profile_id,
                        product_name = :product_name,
                        description = :description,
                        price = :price,
                        is_active = :is_active,
                        available_for_preorder = :available_for_preorder
                    WHERE id = :id
                ");
                $result = $statement->execute([
                    'id' => $this->id,
                    'seller_profile_id' => $this->seller_profile_id,
                    'product_name' => $this->product_name,
                    'description' => $this->description,
                    'price' => $this->price,
                    'is_active' => $this->is_active,
                    'available_for_preorder' => $this->available_for_preorder
                ]);
            } else {
                $statement = $db->prepare("
                    INSERT INTO products (
                        seller_profile_id, product_name, description,
                        price, is_active, available_for_preorder
                    ) VALUES (
                        :seller_profile_id, :product_name, :description,
                        :price, :is_active, :available_for_preorder
                    )
                ");
                $result = $statement->execute([
                    'seller_profile_id' => $this->seller_profile_id,
                    'product_name' => $this->product_name,
                    'description' => $this->description,
                    'price' => $this->price,
                    'is_active' => $this->is_active,
                    'available_for_preorder' => $this->available_for_preorder
                ]);

                if ($result) {
                    $this->id = $db->lastInsertId();
                }
            }

            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function addImage(string $imageUrl, bool $isMain = false): bool {
        $db = Application::$app->db;
        try {
            // If this is main image, unset other main images
            if ($isMain) {
                $statement = $db->prepare("
                    UPDATE product_images 
                    SET is_main = 0 
                    WHERE product_id = :product_id
                ");
                $statement->execute(['product_id' => $this->id]);
            }

            $statement = $db->prepare("
                INSERT INTO product_images (product_id, image_url, is_main)
                VALUES (:product_id, :image_url, :is_main)
            ");
            return $statement->execute([
                'product_id' => $this->id,
                'image_url' => $imageUrl,
                'is_main' => $isMain
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getImages(): array {
        if (empty($this->images) && $this->id) {
            $statement = Application::$app->db->prepare("
                SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY is_main DESC
            ");
            $statement->execute(['product_id' => $this->id]);
            $this->images = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->images;
    }

    public function getMainImage(): ?string {
        $images = $this->getImages();
        foreach ($images as $image) {
            if ($image['is_main']) {
                return $image['image_url'];
            }
        }
        return $images[0]['image_url'] ?? null;
    }

    public static function findBySeller(int $sellerProfileId, bool $activeOnly = true): array {
        $sql = "SELECT * FROM products WHERE seller_profile_id = :seller_profile_id";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY product_name";

        $statement = Application::$app->db->prepare($sql);
        $statement->execute(['seller_profile_id' => $sellerProfileId]);
        return $statement->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    public static function findOne(int $id): ?Product {
        $statement = Application::$app->db->prepare("
            SELECT * FROM products WHERE id = :id
        ");
        $statement->execute(['id' => $id]);
        return $statement->fetchObject(static::class);
    }

    public function delete(): bool {
        if (!$this->id) {
            return false;
        }

        $statement = Application::$app->db->prepare("
            DELETE FROM products WHERE id = :id
        ");
        return $statement->execute(['id' => $this->id]);
    }

    public function getSeller(): ?SellerProfile {
        return SellerProfile::findOne($this->seller_profile_id);
    }
}
