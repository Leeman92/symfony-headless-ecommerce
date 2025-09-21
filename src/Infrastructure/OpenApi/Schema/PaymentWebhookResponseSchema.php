<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentWebhookResponse',
    required: ['received'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Payment', nullable: true),
        new OA\Property(property: 'received', type: 'boolean', example: true),
    ]
)]
final class PaymentWebhookResponseSchema
{
}
