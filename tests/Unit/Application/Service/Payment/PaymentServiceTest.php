<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service\Payment;

use App\Application\Service\Payment\PaymentService;
use App\Application\Service\Payment\PaymentServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Infrastructure\Stripe\StripePaymentGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Stripe\PaymentIntent;

final class PaymentServiceTest extends TestCase
{
    private PaymentRepositoryInterface&MockObject $paymentRepository;

    private StripePaymentGatewayInterface&MockObject $stripeGateway;

    private EntityManagerInterface&MockObject $entityManager;

    private PaymentServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->stripeGateway = $this->createMock(StripePaymentGatewayInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new PaymentService(
            $this->paymentRepository,
            $this->stripeGateway,
            $this->entityManager
        );
    }

    public function testCreatePaymentIntentCreatesPaymentAndPersists(): void
    {
        $order = $this->createOrder();

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'status' => 'requires_payment_method',
            'payment_method' => null,
            'customer' => 'cus_123',
            'metadata' => ['foo' => 'bar'],
        ]);

        $this->paymentRepository->expects(self::once())
            ->method('findOneByOrder')
            ->with($order)
            ->willReturn(null);

        $this->stripeGateway->expects(self::once())
            ->method('createPaymentIntent')
            ->with(self::callback(function (array $params): bool {
                self::assertSame(10000, $params['amount']);
                self::assertSame('usd', $params['currency']);
                self::assertSame('Payment for order ORD-2024-0001', $params['description']);
                return true;
            }))
            ->willReturn($intent);

        $persistedPayments = [];
        $this->paymentRepository->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Payment $payment) use (&$persistedPayments) {
                $persistedPayments[] = $payment;
            });

        $this->entityManager->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $operation) {
                return $operation(new \stdClass());
            });

        $payment = $this->service->createPaymentIntent($order);

        self::assertSame($order, $payment->getOrder());
        self::assertSame('pi_123', $payment->getStripePaymentIntentId());
        self::assertTrue($payment->isPending());
        self::assertSame(['foo' => 'bar'], $payment->getStripeMetadata());
        self::assertCount(1, $persistedPayments);
    }

    public function testConfirmPaymentUpdatesStatusAndPersists(): void
    {
        $order = $this->createOrder();
        $payment = new Payment($order, 'pi_456', $order->getTotal());
        $order->setPayment($payment);

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_456',
            'status' => 'succeeded',
            'payment_method' => 'pm_123',
            'customer' => 'cus_789',
            'charges' => [
                'data' => [
                    [
                        'payment_method_details' => ['type' => 'card', 'card' => ['brand' => 'visa']],
                    ],
                ],
            ],
        ]);

        $this->paymentRepository->expects(self::once())
            ->method('findByStripePaymentIntentId')
            ->with('pi_456')
            ->willReturn($payment);

        $this->stripeGateway->expects(self::once())
            ->method('confirmPaymentIntent')
            ->with('pi_456', ['payment_method' => 'pm_123'])
            ->willReturn($intent);

        $this->paymentRepository->expects(self::once())
            ->method('save')
            ->with($payment);

        $this->entityManager->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $operation) {
                return $operation(new \stdClass());
            });

        $result = $this->service->confirmPayment('pi_456', 'pm_123');

        self::assertSame($payment, $result);
        self::assertTrue($payment->isSucceeded());
        self::assertSame('pm_123', $payment->getStripePaymentMethodId());
    }

    public function testHandleWebhookEventUpdatesPaymentOnFailure(): void
    {
        $order = $this->createOrder();
        $payment = new Payment($order, 'pi_failed', $order->getTotal());
        $order->setPayment($payment);

        $event = Event::constructFrom([
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => PaymentIntent::constructFrom([
                    'id' => 'pi_failed',
                    'payment_method' => 'pm_fail',
                    'last_payment_error' => [
                        'message' => 'Card declined',
                        'code' => 'card_declined',
                    ],
                    'metadata' => ['source' => 'webhook'],
                ]),
            ],
        ]);

        $this->paymentRepository->expects(self::once())
            ->method('findByStripePaymentIntentId')
            ->with('pi_failed')
            ->willReturn($payment);

        $this->paymentRepository->expects(self::once())
            ->method('save')
            ->with($payment);

        $result = $this->service->handleWebhookEvent($event);

        self::assertSame($payment, $result);
        self::assertTrue($payment->isFailed());
        self::assertSame('Card declined', $payment->getFailureReason());
        self::assertSame('pm_fail', $payment->getStripePaymentMethodId());
        self::assertSame(['source' => 'webhook'], $payment->getStripeMetadata());
    }

    private function createOrder(): Order
    {
        $order = new Order(new OrderNumber('ORD-2024-0001'));
        $order->setSubtotal(new Money('100.00'));
        $order->setTaxAmount(Money::zero());
        $order->setShippingAmount(Money::zero());
        $order->setDiscountAmount(Money::zero());
        $order->calculateTotal();

        return $order;
    }
}
