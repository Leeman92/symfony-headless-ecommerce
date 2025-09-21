<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItemRequest',
    required: ['product_id', 'quantity'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'quantity', type: 'integer', example: 2, minimum: 1),
        new OA\Property(property: 'unit_price', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true, description: 'Optional override when pricing externally calculated.'),
    ],
)]
final class OrderItemRequestSchema
{
}
