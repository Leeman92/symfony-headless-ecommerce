<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Payment entity
 */
final class PaymentTest extends TestCase
{
    private Order $order;
    private Payment $payment;

    protected function setUp(): void
    {
        $orderNumber = new OrderNumber('ORD-2024-001');
        $this->order = new Order($orderNumber);
        $this->order->setTotal(new Money('100.00', 'USD'));

        $amount = new Money('100.00', 'USD');
        $this->payment = new Payment($this->order, 'pi_test123456789', $amount);
    }

    public function testPaymentCreation(): void
    {
        self::assertSame($this->order, $this->payment->getOrder());
        self::assertSame('pi_test123456789', $this->payment->getStripePaymentIntentId());
        self::assertSame('100.00', $this->payment->getAmount()->getAmount());
        self::assertSame(100.0, $this->payment->getAmountAsFloat());
        self::assertSame(10000, $this->payment->getAmountInCents());
        self::assertSame('USD', $this->payment->getCurrency());
        self::assertSame(Payment::STATUS_PENDING, $this->payment->getStatus());
        self::assertSame('0.00', $this->payment->getRefundedAmount()->getAmount());
        self::assertTrue($this->payment->isPending());
    }

    public function testPaymentStatusManagement(): void
    {
        // Test initial status
        self::assertTrue($this->payment->isPending());
        self::assertFalse($this->payment->isSucceeded());
        self::assertFalse($this->payment->isFailed());

        // Test status change to processing
        $this->payment->setStatus(Payment::STATUS_PROCESSING);
        self::assertTrue($this->payment->isProcessing());
        self::assertFalse($this->payment->isPending());

        // Test status change to succeeded
        $this->payment->setStatus(Payment::STATUS_SUCCEEDED);
        self::assertTrue($this->payment->isSucceeded());
        self::assertNotNull($this->payment->getPaidAt());

        // Test status change to failed
        $this->payment->setStatus(Payment::STATUS_FAILED);
        self::assertTrue($this->payment->isFailed());
        self::assertNotNull($this->payment->getFailedAt());
    }

    public function testInvalidPaymentStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid payment status: invalid_status');

        $this->payment->setStatus('invalid_status');
    }

    public function testPaymentMethodHandling(): void
    {
        self::assertNull($this->payment->getPaymentMethod());

        $this->payment->setPaymentMethod(Payment::METHOD_CARD);
        self::assertSame(Payment::METHOD_CARD, $this->payment->getPaymentMethod());

        $this->payment->setPaymentMethod(Payment::METHOD_BANK_TRANSFER);
        self::assertSame(Payment::METHOD_BANK_TRANSFER, $this->payment->getPaymentMethod());
    }

    public function testInvalidPaymentMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid payment method: invalid_method');

        $this->payment->setPaymentMethod('invalid_method');
    }

    public function testCurrencyHandling(): void
    {
        $this->payment->setCurrency('eur');
        self::assertSame('EUR', $this->payment->getCurrency());

        $this->payment->setCurrency('GBP');
        self::assertSame('GBP', $this->payment->getCurrency());
    }

    public function testStripeMetadataHandling(): void
    {
        $metadata = ['order_id' => '123', 'customer_type' => 'guest'];
        $this->payment->setStripeMetadata($metadata);

        self::assertSame($metadata, $this->payment->getStripeMetadata());
        self::assertSame('123', $this->payment->getStripeMetadataValue('order_id'));
        self::assertSame('guest', $this->payment->getStripeMetadataValue('customer_type'));
        self::assertNull($this->payment->getStripeMetadataValue('nonexistent'));

        $this->payment->setStripeMetadataValue('new_key', 'new_value');
        self::assertSame('new_value', $this->payment->getStripeMetadataValue('new_key'));
    }

    public function testPaymentMethodDetailsHandling(): void
    {
        $details = [
            'type' => 'card',
            'card' => [
                'brand' => 'visa',
                'last4' => '4242',
                'exp_month' => 12,
                'exp_year' => 2025,
            ],
        ];

        $this->payment->setPaymentMethodDetails($details);
        self::assertSame($details, $this->payment->getPaymentMethodDetails());
    }

    public function testFailureHandling(): void
    {
        $this->payment->setFailureReason('Your card was declined.');
        $this->payment->setFailureCode('card_declined');

        self::assertSame('Your card was declined.', $this->payment->getFailureReason());
        self::assertSame('card_declined', $this->payment->getFailureCode());
    }

    public function testMarkAsSucceeded(): void
    {
        $paymentMethodDetails = ['type' => 'card', 'card' => ['last4' => '4242']];

        $this->payment->markAsSucceeded('pm_test123', $paymentMethodDetails);

        self::assertTrue($this->payment->isSucceeded());
        self::assertSame('pm_test123', $this->payment->getStripePaymentMethodId());
        self::assertSame($paymentMethodDetails, $this->payment->getPaymentMethodDetails());
        self::assertNotNull($this->payment->getPaidAt());
    }

    public function testMarkAsFailed(): void
    {
        $this->payment->markAsFailed('Card was declined', 'card_declined');

        self::assertTrue($this->payment->isFailed());
        self::assertSame('Card was declined', $this->payment->getFailureReason());
        self::assertSame('card_declined', $this->payment->getFailureCode());
        self::assertNotNull($this->payment->getFailedAt());
    }

    public function testRefundHandling(): void
    {
        // Mark payment as succeeded first
        $this->payment->setStatus(Payment::STATUS_SUCCEEDED);

        self::assertSame(0.0, $this->payment->getRefundedAmountAsFloat());
        self::assertSame(100.0, $this->payment->getRemainingAmount()->getAmountAsFloat());
        self::assertFalse($this->payment->isFullyRefunded());
        self::assertFalse($this->payment->isPartiallyRefunded());
        self::assertTrue($this->payment->canBeRefunded());

        // Add partial refund
        $this->payment->addRefund('30.00');

        self::assertSame('30.00', $this->payment->getRefundedAmount()->getAmount());
        self::assertSame(30.0, $this->payment->getRefundedAmountAsFloat());
        self::assertSame(70.0, $this->payment->getRemainingAmount()->getAmountAsFloat());
        self::assertFalse($this->payment->isFullyRefunded());
        self::assertTrue($this->payment->isPartiallyRefunded());
        self::assertTrue($this->payment->isPartiallyRefundedStatus());
        self::assertNotNull($this->payment->getRefundedAt());

        // Add full refund
        $this->payment->addRefund('70.00');

        self::assertSame('100.00', $this->payment->getRefundedAmount()->getAmount());
        self::assertSame(0.0, $this->payment->getRemainingAmount()->getAmountAsFloat());
        self::assertTrue($this->payment->isFullyRefunded());
        self::assertFalse($this->payment->isPartiallyRefunded());
        self::assertTrue($this->payment->isRefunded());
        self::assertFalse($this->payment->canBeRefunded());
    }

    public function testRefundExceedsAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refund amount exceeds payment amount');

        $this->payment->addRefund('150.00');
    }

    public function testRefundAccumulation(): void
    {
        $this->payment->setStatus(Payment::STATUS_SUCCEEDED);

        $this->payment->addRefund('25.00');
        $this->payment->addRefund('25.00');
        $this->payment->addRefund('25.00');

        self::assertSame('75.00', $this->payment->getRefundedAmount()->getAmount());
        self::assertSame(25.0, $this->payment->getRemainingAmount()->getAmountAsFloat());
        self::assertTrue($this->payment->isPartiallyRefunded());
    }

    public function testCanBeRefundedForDifferentStatuses(): void
    {
        // Pending payment cannot be refunded
        self::assertFalse($this->payment->canBeRefunded());

        // Failed payment cannot be refunded
        $this->payment->setStatus(Payment::STATUS_FAILED);
        self::assertFalse($this->payment->canBeRefunded());

        // Succeeded payment can be refunded
        $this->payment->setStatus(Payment::STATUS_SUCCEEDED);
        self::assertTrue($this->payment->canBeRefunded());

        // Fully refunded payment cannot be refunded further
        $this->payment->addRefund('100.00');
        self::assertFalse($this->payment->canBeRefunded());
    }

    public function testPaymentValidation(): void
    {
        // Test valid payment
        self::assertTrue($this->payment->isValid());
        self::assertEmpty($this->payment->getValidationErrors());
    }

    public function testPaymentToString(): void
    {
        self::assertSame('Payment pi_test123456789 - $100.00', (string) $this->payment);

        $this->payment->setCurrency('EUR');
        self::assertSame('Payment pi_test123456789 - €100.00', (string) $this->payment);
    }

    public function testStripeCustomerIdHandling(): void
    {
        self::assertNull($this->payment->getStripeCustomerId());

        $this->payment->setStripeCustomerId('cus_test123');
        self::assertSame('cus_test123', $this->payment->getStripeCustomerId());
    }

    public function testTimestampHandling(): void
    {
        // Test that timestamps are set automatically when status changes
        $beforeSucceeded = new DateTime();
        $this->payment->setStatus(Payment::STATUS_SUCCEEDED);
        $afterSucceeded = new DateTime();

        self::assertNotNull($this->payment->getPaidAt());
        self::assertGreaterThanOrEqual($beforeSucceeded, $this->payment->getPaidAt());
        self::assertLessThanOrEqual($afterSucceeded, $this->payment->getPaidAt());

        // Test manual timestamp setting
        $customDate = new DateTime('2024-01-01 12:00:00');
        $this->payment->setPaidAt($customDate);
        self::assertSame($customDate, $this->payment->getPaidAt());
    }
}
