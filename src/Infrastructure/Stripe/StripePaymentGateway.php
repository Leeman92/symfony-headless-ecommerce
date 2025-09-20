<?php

declare(strict_types=1);

namespace App\Infrastructure\Stripe;

use Stripe\PaymentIntent;
use Stripe\StripeClient;

final class StripePaymentGateway implements StripePaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $stripeClient)
    {
    }

    public function createPaymentIntent(array $params): PaymentIntent
    {
        return $this->stripeClient->paymentIntents->create($params);
    }

    public function confirmPaymentIntent(string $paymentIntentId, array $params = []): PaymentIntent
    {
        return $this->stripeClient->paymentIntents->confirm($paymentIntentId, $params);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripeClient->paymentIntents->retrieve($paymentIntentId);
    }
}
