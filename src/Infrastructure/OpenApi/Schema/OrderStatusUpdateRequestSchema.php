<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderStatusUpdateRequest',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Customer confirmed delivery date'),
        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'shipped_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
final class OrderStatusUpdateRequestSchema
{
}
