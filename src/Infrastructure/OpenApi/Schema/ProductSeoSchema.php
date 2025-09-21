<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductSeo',
    properties: [
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Best hoodie for winter'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Warm and comfortable hoodie for cold days'),
        new OA\Property(property: 'keywords', type: 'string', nullable: true, example: 'hoodie,winter,cozy'),
    ],
)]
final class ProductSeoSchema
{
}
