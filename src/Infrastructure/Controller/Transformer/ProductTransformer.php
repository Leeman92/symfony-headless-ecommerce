<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

use App\Domain\Entity\Category;
use App\Domain\Entity\Product;

final class ProductTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Product $product): array
    {
        $category = $product->getCategory();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => (string) $product->getSlug(),
            'description' => $product->getDescription(),
            'short_description' => $product->getShortDescription(),
            'price' => MoneyTransformer::toArray($product->getPrice()),
            'compare_price' => $product->getComparePrice() ? MoneyTransformer::toArray($product->getComparePrice()) : null,
            'stock' => $product->getStock(),
            'sku' => $product->getSku()?->getValue(),
            'is_active' => $product->isActive(),
            'is_featured' => $product->isFeatured(),
            'track_stock' => $product->isTrackStock(),
            'low_stock_threshold' => $product->getLowStockThreshold(),
            'attributes' => $product->getAttributes(),
            'variants' => $product->getVariants(),
            'images' => $product->getImages(),
            'seo' => $product->getSeoData(),
            'category' => self::transformCategory($category),
            'created_at' => DateTimeTransformer::toString($product->getCreatedAt()),
            'updated_at' => DateTimeTransformer::toString($product->getUpdatedAt()),
        ];
    }

    /**
     * @return array{id: int|null, name: string|null, slug: string|null}|null
     */
    private static function transformCategory(?Category $category): ?array
    {
        if ($category === null) {
            return null;
        }

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlugString(),
        ];
    }
}
