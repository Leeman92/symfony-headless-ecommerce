<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Money',
    required: ['amount', 'currency', 'amount_float', 'formatted'],
    properties: [
        new OA\Property(property: 'amount', type: 'string', example: '1999', description: 'Amount expressed in minor units as a string.'),
        new OA\Property(property: 'currency', type: 'string', example: 'USD', description: 'ISO 4217 currency code.'),
        new OA\Property(property: 'amount_float', type: 'number', format: 'float', example: 19.99, description: 'Amount as a floating point number in major units.'),
        new OA\Property(property: 'formatted', type: 'string', example: '$19.99', description: 'Human readable formatted amount.'),
    ]
)]
final class MoneySchema
{
}
