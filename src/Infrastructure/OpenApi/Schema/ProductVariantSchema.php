<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductVariant',
    required: ['id', 'sku', 'name', 'stock', 'is_default', 'position', 'attributes'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'sku', type: 'string', example: 'SKU-001-RED-M'),
        new OA\Property(property: 'name', type: 'string', example: 'Red Hoodie / Medium'),
        new OA\Property(property: 'price', ref: '#/components/schemas/Money', nullable: true),
        new OA\Property(property: 'compare_price', ref: '#/components/schemas/Money', nullable: true),
        new OA\Property(property: 'stock', type: 'integer', example: 12),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(
            property: 'attributes',
            type: 'object',
            description: 'Variant attribute key/value map (e.g. size, color).',
            example: ['color' => 'red', 'size' => 'M']
        ),
    ]
)]
final class ProductVariantSchema
{
}
