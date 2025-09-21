<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategorySummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Outerwear'),
        new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'outerwear'),
    ]
)]
final class CategorySummarySchema
{
}
