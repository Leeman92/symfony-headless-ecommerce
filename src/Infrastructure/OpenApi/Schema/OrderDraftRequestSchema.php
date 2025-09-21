<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderDraftRequest',
    required: ['items'],
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#App/Infrastructure/OpenApi/Schema/OrderItemRequest'),
        ),
        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD'),
        new OA\Property(property: 'tax', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true),
        new OA\Property(property: 'shipping', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true),
        new OA\Property(property: 'discount', ref: '#App/Infrastructure/OpenApi/Schema/Money', nullable: true),
        new OA\Property(property: 'billing_address', ref: '#App/Infrastructure/OpenApi/Schema/Address', nullable: true),
        new OA\Property(property: 'shipping_address', ref: '#App/Infrastructure/OpenApi/Schema/Address', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
    ],
)]
final class OrderDraftRequestSchema
{
}
