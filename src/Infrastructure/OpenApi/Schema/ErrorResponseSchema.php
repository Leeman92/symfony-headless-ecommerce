<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            type: 'object',
            required: ['message', 'status'],
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invalid request payload'),
                new OA\Property(property: 'status', type: 'integer', example: 400),
            ]
        ),
    ]
)]
final class ErrorResponseSchema
{
}
