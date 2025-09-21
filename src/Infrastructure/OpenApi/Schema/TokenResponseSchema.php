<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TokenResponse',
    required: ['data'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['token', 'user'],
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                new OA\Property(property: 'expires_at', type: 'integer', nullable: true, example: 1736300434, description: 'Unix timestamp when the JWT expires.'),
                new OA\Property(property: 'user', ref: '#App/Infrastructure/OpenApi/Schema/User'),
            ],
        ),
    ],
)]
final class TokenResponseSchema
{
}
