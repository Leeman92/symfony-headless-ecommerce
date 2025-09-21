<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Address',
    required: ['street', 'city', 'state', 'postal_code', 'country'],
    properties: [
        new OA\Property(property: 'street', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'city', type: 'string', example: 'Austin'),
        new OA\Property(property: 'state', type: 'string', example: 'TX'),
        new OA\Property(property: 'postal_code', type: 'string', example: '73301'),
        new OA\Property(property: 'country', type: 'string', example: 'US'),
    ]
)]
final class AddressSchema
{
}
