<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['page', 'limit', 'total', 'total_pages', 'has_next', 'has_previous'],
    properties: [
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 125),
        new OA\Property(property: 'total_pages', type: 'integer', example: 7),
        new OA\Property(property: 'has_next', type: 'boolean', example: true),
        new OA\Property(property: 'has_previous', type: 'boolean', example: false),
    ]
)]
final class PaginationMetaSchema
{
}
