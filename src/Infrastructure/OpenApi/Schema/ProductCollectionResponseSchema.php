<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductCollectionResponse',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#App/Infrastructure/OpenApi/Schema/Product'),
        ),
        new OA\Property(property: 'meta', ref: '#App/Infrastructure/OpenApi/Schema/PaginationMeta'),
    ],
)]
final class ProductCollectionResponseSchema
{
}
