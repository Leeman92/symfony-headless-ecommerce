<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GuestCustomerRequest',
    required: ['email', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'guest@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Sam'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Taylor'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+1-555-000-1234'),
    ]
)]
final class GuestCustomerRequestSchema
{
}
