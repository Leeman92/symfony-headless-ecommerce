<?php

declare(strict_types=1);

namespace App\Infrastructure\Stripe;

use Stripe\PaymentIntent;

interface StripePaymentGatewayInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function createPaymentIntent(array $params): PaymentIntent;

    /**
     * @param array<string, mixed> $params
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $params = []): PaymentIntent;

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent;
}
