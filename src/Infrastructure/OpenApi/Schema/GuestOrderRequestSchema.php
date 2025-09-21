<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GuestOrderRequest',
    allOf: [
        new OA\Schema(ref: '#App/Infrastructure/OpenApi/Schema/OrderDraftRequest'),
        new OA\Schema(
            required: ['guest'],
            properties: [
                new OA\Property(property: 'guest', ref: '#App/Infrastructure/OpenApi/Schema/GuestCustomerRequest'),
            ],
        ),
    ],
)]
final class GuestOrderRequestSchema
{
}
