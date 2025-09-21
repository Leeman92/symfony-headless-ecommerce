<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    required: ['id', 'name', 'slug', 'price', 'stock', 'is_active', 'is_featured', 'track_stock', 'attributes', 'variants', 'images', 'seo', 'category', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Performance Hoodie'),
        new OA\Property(property: 'slug', type: 'string', example: 'performance-hoodie'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Full product description in markdown or HTML'),
        new OA\Property(property: 'short_description', type: 'string', nullable: true, example: 'Warm hoodie for everyday wear'),
        new OA\Property(property: 'price', ref: '#/components/schemas/Money'),
        new OA\Property(property: 'compare_price', ref: '#/components/schemas/Money', nullable: true),
        new OA\Property(property: 'stock', type: 'integer', example: 34),
        new OA\Property(property: 'sku', type: 'string', nullable: true, example: 'HOODIE-001'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_featured', type: 'boolean', example: false),
        new OA\Property(property: 'track_stock', type: 'boolean', example: true),
        new OA\Property(property: 'low_stock_threshold', type: 'integer', nullable: true, example: 5),
        new OA\Property(
            property: 'attributes',
            type: 'object',
            description: 'Product attribute map supporting arbitrary keys/values.',
            example: ['material' => 'Cotton', 'collection' => 'Winter']
        ),
        new OA\Property(
            property: 'variants',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductVariant')
        ),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductImage')
        ),
        new OA\Property(property: 'seo', ref: '#/components/schemas/ProductSeo', nullable: true),
        new OA\Property(property: 'category', ref: '#/components/schemas/CategorySummary', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-02-10T12:34:56+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-02-12T09:15:00+00:00'),
    ]
)]
final class ProductResourceSchema
{
}
