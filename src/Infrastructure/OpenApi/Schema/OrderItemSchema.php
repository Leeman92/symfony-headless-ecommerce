<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItem',
    required: ['id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 101),
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_name', type: 'string', example: 'Performance Hoodie'),
        new OA\Property(property: 'product_sku', type: 'string', nullable: true, example: 'HOODIE-001'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'total_price', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
    ],
)]
final class OrderItemSchema
{
}
