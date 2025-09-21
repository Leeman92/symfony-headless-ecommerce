<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductWriteRequest',
    required: ['name', 'price'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Performance Hoodie'),
        new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'performance-hoodie'),
        new OA\Property(
            property: 'price',
            type: 'object',
            required: ['amount', 'currency'],
            properties: [
                new OA\Property(property: 'amount', type: 'string', example: '9900'),
                new OA\Property(property: 'currency', type: 'string', example: 'USD'),
            ],
            description: 'Money payload. Accepts string amounts but object format is recommended.',
        ),
        new OA\Property(property: 'compare_price', type: 'object', nullable: true, ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD', description: 'Optional currency hint when price is provided as a scalar.'),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 3, description: 'Provide either category_id or category_slug (or category.id/category.slug) to assign a category.'),
        new OA\Property(property: 'category_slug', type: 'string', nullable: true, example: 'outerwear'),
        new OA\Property(
            property: 'category',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'outerwear'),
            ],
        ),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'short_description', type: 'string', nullable: true),
        new OA\Property(property: 'sku', type: 'string', nullable: true, example: 'HOODIE-001'),
        new OA\Property(property: 'stock', type: 'integer', nullable: true, example: 25),
        new OA\Property(property: 'track_stock', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'low_stock_threshold', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'is_featured', type: 'boolean', nullable: true, example: false),
        new OA\Property(property: 'attributes', type: 'object', nullable: true, example: ['material' => 'Cotton']),
        new OA\Property(
            property: 'variants',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                required: ['sku'],
                properties: [
                    new OA\Property(property: 'sku', type: 'string', example: 'HOODIE-001-RED-M'),
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                    new OA\Property(property: 'price', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true),
                    new OA\Property(property: 'compare_price', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true),
                    new OA\Property(property: 'stock', type: 'integer', nullable: true, example: 10),
                    new OA\Property(property: 'is_default', type: 'boolean', nullable: true, example: true),
                    new OA\Property(property: 'position', type: 'integer', nullable: true, example: 0),
                    new OA\Property(property: 'attributes', type: 'object', nullable: true, example: ['size' => 'M', 'color' => 'red']),
                ],
            ),
        ),
        new OA\Property(
            property: 'images',
            type: 'array',
            nullable: true,
            description: 'Optional product images referencing uploaded media assets.',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', nullable: true),
                    new OA\Property(property: 'asset_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', nullable: true),
                    new OA\Property(property: 'alt', type: 'string', nullable: true),
                    new OA\Property(property: 'is_primary', type: 'boolean', nullable: true),
                    new OA\Property(property: 'position', type: 'integer', nullable: true),
                ],
            ),
        ),
        new OA\Property(property: 'seo', ref: '#App/Infrastructure/OpenApi/Schema/ProductSeo', nullable: true),
        new OA\Property(
            property: 'metadata',
            type: 'object',
            nullable: true,
            description: 'Optional metadata bag for integrations.',
        ),
    ],
)]
final class ProductWriteRequestSchema
{
}
