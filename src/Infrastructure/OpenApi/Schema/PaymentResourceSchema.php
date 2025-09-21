<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Payment',
    required: ['id', 'status', 'amount', 'refunded_amount', 'stripe_payment_intent_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 88),
        new OA\Property(property: 'status', type: 'string', example: 'requires_confirmation'),
        new OA\Property(property: 'amount', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'refunded_amount', ref: '#App/Infrastructure/OpenApi/Schema/Money'),
        new OA\Property(property: 'stripe_payment_intent_id', type: 'string', example: 'pi_3OBSWLLpBzs8'),
        new OA\Property(property: 'stripe_payment_method_id', type: 'string', nullable: true, example: 'pm_1OBSWLLpBzs8'),
        new OA\Property(property: 'stripe_customer_id', type: 'string', nullable: true, example: 'cus_Qwe123'),
        new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'card'),
        new OA\Property(property: 'payment_method_details', type: 'object', nullable: true, description: 'Raw payment method details from Stripe.'),
        new OA\Property(property: 'failure_reason', type: 'string', nullable: true),
        new OA\Property(property: 'failure_code', type: 'string', nullable: true),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'failed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'refunded_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-02-12T12:00:00+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-02-12T12:05:00+00:00'),
    ],
)]
final class PaymentResourceSchema
{
}
