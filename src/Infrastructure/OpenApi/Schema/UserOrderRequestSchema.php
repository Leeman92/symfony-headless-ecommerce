<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserOrderRequest',
    allOf: [
        new OA\Schema(ref: '#App/Infrastructure/OpenApi/Schema/OrderDraftRequest'),
    ],
)]
final class UserOrderRequestSchema
{
}
