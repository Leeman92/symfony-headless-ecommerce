<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderCustomerUser',
    required: ['type', 'id', 'email', 'first_name', 'last_name', 'full_name'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'user'),
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'customer@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Ada'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Lovelace'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Ada Lovelace'),
    ]
)]
final class OrderCustomerUserSchema
{
}
