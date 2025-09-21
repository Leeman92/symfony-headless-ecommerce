<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductImage',
    required: ['id', 'asset_id', 'url', 'alt', 'is_primary', 'position'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 42),
        new OA\Property(property: 'asset_id', type: 'integer', example: 7),
        new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://cdn.example.com/products/hoodie.jpg'),
        new OA\Property(property: 'alt', type: 'string', nullable: true, example: 'Red hoodie on hanger'),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'position', type: 'integer', example: 1),
    ],
)]
final class ProductImageSchema
{
}
