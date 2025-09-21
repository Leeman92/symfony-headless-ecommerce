<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#App/Infrastructure/OpenApi/Schema/Product'),
    ],
)]
final class ProductResponseSchema
{
}
