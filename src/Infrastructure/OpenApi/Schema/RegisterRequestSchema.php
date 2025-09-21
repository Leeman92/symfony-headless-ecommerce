<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['email', 'password', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'Str0ng-P@ssword!'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Ada'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Lovelace'),
        new OA\Property(property: 'roles', type: 'array', nullable: true, items: new OA\Items(type: 'string'), example: ['ROLE_CUSTOMER']),
    ],
)]
final class RegisterRequestSchema
{
}
