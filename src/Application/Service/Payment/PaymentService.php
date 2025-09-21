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
use Stripe\PaymentMethod;
use Stripe\StripeObject;

use function is_array;
use function is_string;
use function sprintf;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly StripePaymentGatewayInterface $stripeGateway,
        private readonly EntityManagerInterface $entityManager,
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
                        'order_id' => (string) $order->getId(),
                        'order_number' => (string) $order->getOrderNumber(),
                    ],
                    'receipt_email' => $order->getCustomerEmailString() ?? '',
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
        $objectPayload = $event->data['object'] ?? $this->extractObjectFromEvent($event);

        $object = $this->normalizePaymentIntent($objectPayload);

        if ($object === null) {
            return null;
        }

        $paymentIntentId = $this->resolveStripeId($object->id ?? null);
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
                $paymentMethodId = $this->resolvePaymentMethodId($object->payment_method ?? null);
                $payment->markAsSucceeded($paymentMethodId, $this->extractPaymentMethodDetails($object));
                $updatePerformed = true;
                break;
            case 'payment_intent.payment_failed':
                [$failureMessage, $failureCode] = $this->extractFailureInformation($object->last_payment_error ?? null);
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
                $status = $this->resolveStripeString($object->status ?? null);
                if ($status !== null) {
                    $updatePerformed = $this->applyStripeStatus($payment, $status);
                }
                break;
        }

        if ($updatePerformed) {
            $updatedPaymentMethodId = $this->resolvePaymentMethodId($object->payment_method ?? null);
            if ($updatedPaymentMethodId !== null) {
                $payment->setStripePaymentMethodId($updatedPaymentMethodId);
            }
            $payment->setStripeMetadata($this->stripeObjectToArray($object->metadata ?? null));
            $this->paymentRepository->save($payment);
        }

        return $payment;
    }

    private function synchronisePaymentWithIntent(Payment $payment, PaymentIntent $intent): void
    {
        $payment->setStripePaymentMethodId($this->resolvePaymentMethodId($intent->payment_method ?? null));
        $payment->setStripeCustomerId($this->resolveStripeId($intent->customer ?? null));
        $payment->setStripeMetadata($this->stripeObjectToArray($intent->metadata ?? null));

        $status = $this->resolveStripeString($intent->status ?? null);
        if ($status !== null) {
            $this->applyStripeStatus($payment, $status);
        }
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
        $payload = $intent->toArray();
        $charge = $payload['charges']['data'][0] ?? null;

        if (!is_array($charge)) {
            return null;
        }

        $details = $charge['payment_method_details'] ?? null;
        if ($details instanceof StripeObject) {
            return $details->toArray();
        }

        return is_array($details) ? $details : null;
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

    /**
     * @template TReturn
     * @param callable():TReturn $operation
     * @return TReturn
     */
    private function runInTransaction(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(static fn () => $operation());
    }

    private function normalizePaymentIntent(mixed $payload): ?PaymentIntent
    {
        if ($payload instanceof PaymentIntent) {
            return $payload;
        }

        if ($payload instanceof StripeObject) {
            return PaymentIntent::constructFrom($payload->toArray());
        }

        if (is_array($payload)) {
            return PaymentIntent::constructFrom($payload);
        }

        return null;
    }

    private function resolvePaymentMethodId(mixed $paymentMethod): ?string
    {
        if ($paymentMethod instanceof PaymentMethod) {
            return $paymentMethod->id;
        }

        if ($paymentMethod instanceof StripeObject) {
            $data = $paymentMethod->toArray();

            return isset($data['id']) && is_string($data['id']) ? $data['id'] : null;
        }

        return is_string($paymentMethod) ? $paymentMethod : null;
    }

    private function extractObjectFromEvent(Event $event): mixed
    {
        $payload = $event->data->toArray();

        return $payload['object'] ?? $payload;
    }

    /**
     * @param array<string, mixed>|StripeObject|null $error
     * @return array{0: string, 1: string|null}
     */
    private function extractFailureInformation(mixed $error): array
    {
        if ($error === null) {
            return ['Payment failed', null];
        }

        if ($error instanceof StripeObject) {
            $error = $error->toArray();
        }

        $message = $error['message'] ?? 'Payment failed';
        $code = $error['code'] ?? null;

        return [is_string($message) ? $message : 'Payment failed', is_string($code) ? $code : null];
    }

    private function resolveStripeId(mixed $value): ?string
    {
        if ($value instanceof StripeObject) {
            $data = $value->toArray();

            return isset($data['id']) && is_string($data['id']) ? $data['id'] : null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function resolveStripeString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
