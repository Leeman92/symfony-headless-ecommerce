<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GuestOrderRequest',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/OrderDraftRequest'),
        new OA\Schema(
            required: ['guest'],
            properties: [
                new OA\Property(property: 'guest', ref: '#/components/schemas/GuestCustomerRequest'),
            ]
        ),
    ]
)]
final class GuestOrderRequestSchema
{
}
