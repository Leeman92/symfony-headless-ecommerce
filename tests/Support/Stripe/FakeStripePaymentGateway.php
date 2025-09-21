<?php

declare(strict_types=1);

namespace App\Tests\Support\Stripe;

use App\Infrastructure\Stripe\StripePaymentGatewayInterface;
use Stripe\PaymentIntent;

/**
 * Simple in-memory Stripe gateway used in functional tests to avoid
 * hitting the real Stripe API.
 */
/**
 * @phpstan-type PaymentIntentCreateParams array{
 *   amount: int,
 *   currency: string,
 *   application_fee_amount?: int,
 *   automatic_payment_methods?: array{allow_redirects?: string, enabled: bool},
 *   capture_method?: string,
 *   confirm?: bool,
 *   confirmation_method?: string,
 *   confirmation_token?: string,
 *   customer?: string,
 *   description?: string,
 *   error_on_requires_action?: bool,
 *   excluded_payment_method_types?: list<string>,
 *   expand?: list<string>,
 *   mandate?: string,
 *   mandate_data?: array{
 *     customer_acceptance: array{
 *       accepted_at?: int,
 *       offline?: array<string, mixed>,
 *       online?: array{ip_address: string, user_agent: string},
 *       type: string
 *     }
 *   }|null,
 *   metadata?: array<string, string>,
 *   off_session?: array<string, mixed>|bool|string,
 *   on_behalf_of?: string,
 *   payment_method?: string,
 *   payment_method_configuration?: string,
 *   // WICHTIG: 'type' ist Pflicht, Rest frei -> kein array{} verwenden
 *   payment_method_data?: (array{type: string} & array<string, mixed>),
 *   // Hier genügt eine Map – Stripe erwartet viele verschachtelte Optionen
 *   payment_method_options?: array<string, mixed>,
 *   payment_method_types?: list<string>,
 *   radar_options?: array{session?: string},
 *   receipt_email?: string,
 *   return_url?: string,
 *   setup_future_usage?: string,
 *   shipping?: array{
 *     address: array{
 *       city?: string, country?: string, line1?: string, line2?: string,
 *       postal_code?: string, state?: string
 *     },
 *     carrier?: string,
 *     name: string,
 *     phone?: string,
 *     tracking_number?: string
 *   },
 *   statement_descriptor?: string,
 *   statement_descriptor_suffix?: string,
 *   transfer_data?: array{amount?: int, destination: string},
 *   transfer_group?: string,
 *   use_stripe_sdk?: bool
 * }
 */
final class FakeStripePaymentGateway implements StripePaymentGatewayInterface
{
    /** @var array<string, PaymentIntent> */
    private array $intents = [];

    /**
     * @phpstan-param PaymentIntentCreateParams|null $params
     */
    public function createPaymentIntent(?array $params): PaymentIntent
    {
        $id = 'pi_'.bin2hex(random_bytes(5));

        $intent = PaymentIntent::constructFrom([
            'id' => $id,
            'status' => 'requires_confirmation',
            'amount' => $params['amount'] ?? 0,
            'currency' => $params['currency'] ?? 'usd',
            'client_secret' => 'secret_'.bin2hex(random_bytes(5)),
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
