<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Order',
    required: ['id', 'order_number', 'status', 'currency', 'subtotal', 'tax', 'shipping', 'discount', 'total', 'customer', 'billing_address', 'shipping_address', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 501),
        new OA\Property(property: 'order_number', type: 'string', example: 'ORD-2024-0001'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'currency', type: 'string', example: 'USD'),
        new OA\Property(property: 'subtotal', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'tax', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'shipping', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'discount', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'total', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(
            property: 'customer',
            oneOf: [
                new OA\Schema(ref: '#App/Infrastructure/OpenApi/Schema/OrderCustomerUser'),
                new OA\Schema(ref: '#App/Infrastructure/OpenApi/Schema/OrderCustomerGuest'),
            ],
            nullable: true,
        ),
        new OA\Property(property: 'billing_address', ref: '#App/Infrastructure/OpenApi/Schema/Address', nullable: true),
        new OA\Property(property: 'shipping_address', ref: '#App/Infrastructure/OpenApi/Schema/Address', nullable: true),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#App/Infrastructure/OpenApi/Schema/OrderItem'),
            nullable: true,
        ),
        new OA\Property(property: 'payment', ref: '#App/Infrastructure/OpenApi/Schema/Payment', nullable: true),
        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'shipped_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-02-12T12:00:00+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-02-12T12:05:00+00:00'),
    ],
)]
final class OrderResourceSchema
{
}
