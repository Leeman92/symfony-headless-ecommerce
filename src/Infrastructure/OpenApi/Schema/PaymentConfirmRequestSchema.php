<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentConfirmRequest',
    properties: [
        new OA\Property(property: 'payment_method_id', type: 'string', nullable: true, example: 'pm_12345'),
    ],
)]
final class PaymentConfirmRequestSchema
{
}
