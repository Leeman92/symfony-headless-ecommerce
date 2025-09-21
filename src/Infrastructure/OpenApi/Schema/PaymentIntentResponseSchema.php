<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentIntentResponse',
    required: ['data'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['payment', 'order'],
            properties: [
                new OA\Property(property: 'payment', ref: '#/components/schemas/Payment'),
                new OA\Property(property: 'order', ref: '#/components/schemas/Order'),
            ]
        ),
    ]
)]
final class PaymentIntentResponseSchema
{
}
