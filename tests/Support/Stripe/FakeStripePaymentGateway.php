<?php

declare(strict_types=1);

namespace App\Tests\Support\Stripe;

use App\Infrastructure\Stripe\StripePaymentGatewayInterface;
use Stripe\PaymentIntent;

/**
 * Simple in-memory Stripe gateway used in functional tests to avoid
 * hitting the real Stripe API.
 */
final class FakeStripePaymentGateway implements StripePaymentGatewayInterface
{
    /** @var array<string, PaymentIntent> */
    private array $intents = [];

    public function createPaymentIntent(array $params): PaymentIntent
    {
        $id = $params['id'] ?? ('pi_' . bin2hex(random_bytes(5)));

        $intent = PaymentIntent::constructFrom([
            'id' => $id,
            'status' => 'requires_confirmation',
            'amount' => $params['amount'] ?? 0,
            'currency' => $params['currency'] ?? 'usd',
            'client_secret' => 'secret_' . bin2hex(random_bytes(5)),
            'payment_method' => null,
            'charges' => (object) ['data' => []],
            'metadata' => $params['metadata'] ?? [],
        ]);

        $this->intents[$id] = $intent;

        return $intent;
    }

    public function confirmPaymentIntent(string $paymentIntentId, array $params = []): PaymentIntent
    {
        $intent = $this->retrievePaymentIntent($paymentIntentId);

        $intent = PaymentIntent::constructFrom([
            'id' => $intent->id,
            'status' => 'succeeded',
            'payment_method' => $params['payment_method'] ?? 'pm_test',
            'charges' => (object) ['data' => [
                (object) ['payment_method_details' => ['card' => ['brand' => 'visa']]],
            ]],
            'metadata' => $intent->metadata ?? [],
        ]);

        $this->intents[$paymentIntentId] = $intent;

        return $intent;
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        if (!isset($this->intents[$paymentIntentId])) {
            $this->intents[$paymentIntentId] = PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'requires_confirmation',
                'metadata' => [],
            ]);
        }

        return $this->intents[$paymentIntentId];
    }
}
