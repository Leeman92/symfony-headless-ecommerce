<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'email', 'first_name', 'last_name', 'roles', 'is_active', 'is_verified'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Ada'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Lovelace'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_CUSTOMER']),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_verified', type: 'boolean', example: false),
    ]
)]
final class UserResourceSchema
{
}
