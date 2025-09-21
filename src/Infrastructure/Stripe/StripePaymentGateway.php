<?php

declare(strict_types=1);

namespace App\Infrastructure\Stripe;

use Stripe\PaymentIntent;
use Stripe\StripeClient;

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
final class StripePaymentGateway implements StripePaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $stripeClient)
    {
    }

    /**
     * @phpstan-param PaymentIntentCreateParams|null $params
     */
    public function createPaymentIntent(?array $params): PaymentIntent
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
