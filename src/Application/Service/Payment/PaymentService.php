<?php

declare(strict_types=1);

namespace App\Application\Service\Payment;

use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\Exception\PaymentProcessingException;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Infrastructure\Stripe\StripePaymentGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeObject;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly StripePaymentGatewayInterface $stripeGateway,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createPaymentIntent(Order $order): Payment
    {
        return $this->runInTransaction(function () use ($order) {
            $existing = $this->paymentRepository->findOneByOrder($order);
            if ($existing !== null) {
                return $existing;
            }

            $amount = $order->getTotal()->getAmountInCents();
            if ($amount <= 0) {
                throw new PaymentProcessingException('Order total must be greater than zero to create a payment.');
            }

            try {
                $intent = $this->stripeGateway->createPaymentIntent([
                    'amount' => $amount,
                    'currency' => strtolower($order->getCurrency()),
                    'metadata' => [
                        'order_id' => $order->getId(),
                        'order_number' => (string) $order->getOrderNumber(),
                    ],
                    'receipt_email' => $order->getCustomerEmailString(),
                    'description' => sprintf('Payment for order %s', $order->getOrderNumber()),
                ]);
            } catch (ApiErrorException $exception) {
                throw new PaymentProcessingException('Unable to create Stripe payment intent', $exception);
            }

            $payment = new Payment($order, $intent->id, $order->getTotal());
            $order->setPayment($payment);

            $this->synchronisePaymentWithIntent($payment, $intent);

            $this->paymentRepository->save($payment);

            return $payment;
        });
    }

    public function confirmPayment(string $paymentIntentId, ?string $paymentMethodId = null): Payment
    {
        return $this->runInTransaction(function () use ($paymentIntentId, $paymentMethodId) {
            $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
            if ($payment === null) {
                throw new PaymentProcessingException("Unable to locate payment for intent {$paymentIntentId}");
            }

            $params = [];
            if ($paymentMethodId !== null) {
                $params['payment_method'] = $paymentMethodId;
            }

            try {
                $intent = $this->stripeGateway->confirmPaymentIntent($paymentIntentId, $params);
            } catch (ApiErrorException $exception) {
                throw new PaymentProcessingException('Stripe payment confirmation failed', $exception);
            }

            $this->synchronisePaymentWithIntent($payment, $intent);

            $this->paymentRepository->save($payment);

            return $payment;
        });
    }

    public function handleWebhookEvent(Event $event): ?Payment
    {
        $object = $event->data['object'] ?? $event->data->object ?? null;
        if (!$object instanceof PaymentIntent) {
            return null;
        }

        $paymentIntentId = $object->id;
        if ($paymentIntentId === null) {
            return null;
        }

        $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
        if ($payment === null) {
            return null;
        }

        $updatePerformed = false;

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $payment->markAsSucceeded($object->payment_method, $this->extractPaymentMethodDetails($object));
                $updatePerformed = true;
                break;
            case 'payment_intent.payment_failed':
                $failureMessage = $object->last_payment_error->message ?? 'Payment failed';
                $failureCode = $object->last_payment_error->code ?? null;
                $payment->markAsFailed($failureMessage, $failureCode);
                $updatePerformed = true;
                break;
            case 'payment_intent.processing':
                $payment->setStatus(Payment::STATUS_PROCESSING);
                $updatePerformed = true;
                break;
            case 'payment_intent.canceled':
                $payment->setStatus(Payment::STATUS_CANCELLED);
                $updatePerformed = true;
                break;
            default:
                $intentStatus = $object->status;
                if (is_string($intentStatus)) {
                    $updatePerformed = $this->applyStripeStatus($payment, $intentStatus);
                }
                break;
        }

        if ($updatePerformed) {
            $payment->setStripePaymentMethodId($object->payment_method ?? $payment->getStripePaymentMethodId());
            $payment->setStripeMetadata($this->stripeObjectToArray(isset($object->metadata) ? $object->metadata : null));
            $this->paymentRepository->save($payment);
        }

        return $payment;
    }

    private function synchronisePaymentWithIntent(Payment $payment, PaymentIntent $intent): void
    {
        $payment->setStripePaymentMethodId($intent->payment_method ?? null);
        $payment->setStripeCustomerId($intent->customer ?? null);
        $payment->setStripeMetadata($this->stripeObjectToArray(isset($intent->metadata) ? $intent->metadata : null));

        $this->applyStripeStatus($payment, $intent->status ?? '');
    }

    private function applyStripeStatus(Payment $payment, string $status): bool
    {
        return match ($status) {
            'succeeded' => (bool) $payment->markAsSucceeded($payment->getStripePaymentMethodId(), null),
            'processing', 'requires_capture' => (bool) $payment->setStatus(Payment::STATUS_PROCESSING),
            'requires_payment_method', 'requires_action', 'requires_confirmation' => (bool) $payment->setStatus(Payment::STATUS_PENDING),
            'canceled' => (bool) $payment->setStatus(Payment::STATUS_CANCELLED),
            'requires_refund' => (bool) $payment->setStatus(Payment::STATUS_REFUNDED),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractPaymentMethodDetails(PaymentIntent $intent): ?array
    {
        if (!isset($intent->charges) || !isset($intent->charges->data[0])) {
            return null;
        }

        $charge = $intent->charges->data[0];
        if (isset($charge->payment_method_details)) {
            return $this->stripeObjectToArray($charge->payment_method_details);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stripeObjectToArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof StripeObject) {
            return $value->toArray();
        }

        return null;
    }

    private function runInTransaction(callable $operation)
    {
        if (method_exists($this->entityManager, 'wrapInTransaction')) {
            return $this->entityManager->wrapInTransaction($operation);
        }

        return $this->entityManager->transactional(static function ($em) use ($operation) {
            return $operation();
        });
    }
}
