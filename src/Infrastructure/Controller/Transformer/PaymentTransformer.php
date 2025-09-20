<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

use App\Domain\Entity\Payment;

final class PaymentTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Payment $payment): array
    {
        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus(),
            'amount' => MoneyTransformer::toArray($payment->getAmount()),
            'refunded_amount' => MoneyTransformer::toArray($payment->getRefundedAmount()),
            'stripe_payment_intent_id' => $payment->getStripePaymentIntentId(),
            'stripe_payment_method_id' => $payment->getStripePaymentMethodId(),
            'stripe_customer_id' => $payment->getStripeCustomerId(),
            'payment_method' => $payment->getPaymentMethod(),
            'payment_method_details' => $payment->getPaymentMethodDetails(),
            'failure_reason' => $payment->getFailureReason(),
            'failure_code' => $payment->getFailureCode(),
            'metadata' => $payment->getStripeMetadata(),
            'paid_at' => DateTimeTransformer::toString($payment->getPaidAt()),
            'failed_at' => DateTimeTransformer::toString($payment->getFailedAt()),
            'refunded_at' => DateTimeTransformer::toString($payment->getRefundedAt()),
            'created_at' => DateTimeTransformer::toString($payment->getCreatedAt()),
            'updated_at' => DateTimeTransformer::toString($payment->getUpdatedAt()),
        ];
    }
}
