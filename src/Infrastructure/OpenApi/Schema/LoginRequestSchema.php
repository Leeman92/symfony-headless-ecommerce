<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['username', 'password'],
    properties: [
        new OA\Property(property: 'username', type: 'string', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'secret'),
    ],
)]
final class LoginRequestSchema
{
}
