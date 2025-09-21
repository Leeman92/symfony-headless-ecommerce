<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderCustomerGuest',
    required: ['type'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'guest'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'guest@example.com'),
        new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'Sam'),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Taylor'),
        new OA\Property(property: 'full_name', type: 'string', nullable: true, example: 'Sam Taylor'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+1-555-000-1234'),
    ]
)]
final class OrderCustomerGuestSchema
{
}
