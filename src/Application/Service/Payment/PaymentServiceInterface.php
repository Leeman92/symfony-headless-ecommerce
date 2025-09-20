<?php

declare(strict_types=1);

namespace App\Application\Service\Payment;

use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use Stripe\Event;

interface PaymentServiceInterface
{
    public function createPaymentIntent(Order $order): Payment;

    public function confirmPayment(string $paymentIntentId, ?string $paymentMethodId = null): Payment;

    public function handleWebhookEvent(Event $event): ?Payment;
}
