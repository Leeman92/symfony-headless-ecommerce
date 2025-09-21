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
        $comparePrice = $product->getComparePrice();
        $variants = [];
        foreach ($product->getVariants() as $variant) {
            $variantPrice = $variant->getPrice();
            $variantComparePrice = $variant->getComparePrice();

            $variants[] = [
                'id' => $variant->getId(),
                'sku' => $variant->getSku()->getValue(),
                'name' => $variant->getName(),
                'price' => $variantPrice !== null ? MoneyTransformer::toArray($variantPrice) : null,
                'compare_price' => $variantComparePrice !== null ? MoneyTransformer::toArray($variantComparePrice) : null,
                'stock' => $variant->getStock(),
                'is_default' => $variant->isDefault(),
                'position' => $variant->getPosition(),
                'attributes' => $variant->getAttributeMap(),
            ];
        }

        $images = $product->getImagePayload();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => (string) $product->getSlug(),
            'description' => $product->getDescription(),
            'short_description' => $product->getShortDescription(),
            'price' => MoneyTransformer::toArray($product->getPrice()),
            'compare_price' => $comparePrice !== null ? MoneyTransformer::toArray($comparePrice) : null,
            'stock' => $product->getStock(),
            'sku' => $product->getSku()?->getValue(),
            'is_active' => $product->isActive(),
            'is_featured' => $product->isFeatured(),
            'track_stock' => $product->isTrackStock(),
            'low_stock_threshold' => $product->getLowStockThreshold(),
            'attributes' => $product->getAttributes(),
            'variants' => $variants,
            'images' => $images,
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
