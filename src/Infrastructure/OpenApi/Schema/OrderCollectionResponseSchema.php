<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderCollectionResponse',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#App/Infrastructure/OpenApi/Schema/Order'),
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            required: ['limit', 'count'],
            properties: [
                new OA\Property(property: 'limit', type: 'integer', example: 10),
                new OA\Property(property: 'count', type: 'integer', example: 2),
            ],
        ),
    ],
)]
final class OrderCollectionResponseSchema
{
}
