<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service\Order;

use App\Application\Service\Order\GuestCustomerData;
use App\Application\Service\Order\OrderDraft;
use App\Application\Service\Order\OrderItemDraft;
use App\Application\Service\Order\OrderService;
use App\Application\Service\Order\OrderServiceInterface;
use App\Application\Service\Product\ProductServiceInterface;
use App\Domain\Entity\Category;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidOrderDataException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use App\Domain\ValueObject\Slug;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;

    private ProductServiceInterface&MockObject $productService;

    private EntityManagerInterface&MockObject $entityManager;

    private OrderServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->productService = $this->createMock(ProductServiceInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->service = new OrderService(
            $this->orderRepository,
            $this->productService,
            $this->entityManager
        );
    }

    public function testCreateGuestOrderBuildsAggregateAndReservesStock(): void
    {
        $product = $this->createProduct('USD');
        $product->setStock(10);

        $guest = new GuestCustomerData('guest@example.com', 'Jane', 'Doe', '+1234567890');
        $draft = new OrderDraft([
            new OrderItemDraft(1, 2),
        ]);

        $this->entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->productService->expects(self::once())
            ->method('reserveStock')
            ->with(1, 2)
            ->willReturnCallback(function (int $_productId, int $quantity) use ($product) {
                $product->decreaseStock($quantity);
                return $product;
            });

        $savedOrders = [];
        $this->orderRepository->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Order $order) use (&$savedOrders) {
                $savedOrders[] = $order;
            });

        $order = $this->service->createGuestOrder($draft, $guest);

        self::assertCount(1, $savedOrders);
        self::assertSame($order, $savedOrders[0]);
        self::assertTrue($order->isGuestOrder());
        self::assertSame('guest@example.com', $order->getGuestEmail()?->getValue());
        self::assertSame('Jane Doe', $order->getGuestFullName());
        self::assertSame('USD', $order->getCurrency());
        self::assertSame('200.00', $order->getSubtotal()->getAmount());
        self::assertSame('200.00', $order->getTotal()->getAmount());
    }

    public function testCreateUserOrderAppliesAdjustments(): void
    {
        $product = $this->createProduct('USD', '50.00');
        $product->setStock(5);

        $user = new User(new Email('user@example.com'), new PersonName('John', 'Smith'));
        $draft = new OrderDraft(
            [new OrderItemDraft(5, 1)],
            'USD',
            new Money('8.00', 'USD'),
            new Money('5.00', 'USD'),
            new Money('3.00', 'USD'),
            new Address('123 Main St', 'New York', 'NY', '10001', 'US'),
            new Address('123 Main St', 'New York', 'NY', '10001', 'US'),
            'Leave at the front desk',
            ['source' => 'mobile']
        );

        $this->entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->productService->expects(self::once())
            ->method('reserveStock')
            ->with(5, 1)
            ->willReturn($product);

        $this->orderRepository->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Order::class));

        $order = $this->service->createUserOrder($user, $draft);

        self::assertSame($user, $order->getCustomer());
        self::assertFalse($order->isGuestOrder());
        self::assertSame('USD', $order->getCurrency());
        self::assertSame('50.00', $order->getSubtotal()->getAmount());
        self::assertSame('60.00', $order->getTotal()->getAmount());
        self::assertSame('mobile', $order->getMetadataValue('source'));
        self::assertSame('Leave at the front desk', $order->getNotes());
    }

    public function testCreateOrderThrowsOnCurrencyMismatch(): void
    {
        $product = $this->createProduct('EUR');
        $product->setStock(5);

        $draft = new OrderDraft([new OrderItemDraft(99, 1)], 'USD');

        $this->entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->productService->expects(self::once())
            ->method('reserveStock')
            ->with(99, 1)
            ->willReturn($product);

        $this->orderRepository->expects(self::never())
            ->method('save');

        $this->expectException(InvalidOrderDataException::class);
        $this->expectExceptionMessage('All order items must share the same currency');

        $this->service->createUserOrder($this->createUser(), $draft);
    }

    public function testConvertGuestOrderToUserClearsGuestData(): void
    {
        $order = $this->createGuestOrderEntity();
        $user = $this->createUser();

        $this->entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->orderRepository->expects(self::once())
            ->method('save')
            ->with($order);

        $result = $this->service->convertGuestOrderToUser($order, $user);

        self::assertSame($order, $result);
        self::assertSame($user, $order->getCustomer());
        self::assertFalse($order->isGuestOrder());
        self::assertNull($order->getGuestEmail());
        self::assertNull($order->getGuestName());
        self::assertNull($order->getGuestPhone());
    }

    public function testConvertGuestOrderToUserFailsWhenAlreadyAssigned(): void
    {
        $order = $this->createGuestOrderEntity();
        $user = $this->createUser();
        $order->setCustomer($user);

        $this->entityManager->expects(self::never())
            ->method('wrapInTransaction');

        $this->orderRepository->expects(self::never())
            ->method('save');

        $this->expectException(InvalidOrderDataException::class);

        $this->service->convertGuestOrderToUser($order, $user);
    }

    private function createUser(): User
    {
        return new User(new Email('user@example.com'), new PersonName('John', 'Smith'));
    }

    private function createProduct(string $currency, string $amount = '100.00'): Product
    {
        $category = new Category('Electronics', new Slug('electronics'));
        $product = new Product('Gaming Laptop', new Slug('gaming-laptop'), new Money($amount, $currency), $category);
        $product->setStock(10);

        return $product;
    }

    private function createGuestOrderEntity(): Order
    {
        $product = $this->createProduct('USD');
        $order = new Order(OrderNumber::generate());
        $order->setGuestEmail(new Email('guest@example.com'));
        $order->setGuestName(new PersonName('Jane', 'Doe'));
        $order->setGuestPhone(new Phone('+1234567890'));

        $item = new OrderItem($product, 1);
        $order->addItem($item);

        $order->setSubtotal($item->getTotalPrice());
        $order->setTaxAmount(Money::zero('USD'));
        $order->setShippingAmount(Money::zero('USD'));
        $order->setDiscountAmount(Money::zero('USD'));
        $order->calculateTotal();

        return $order;
    }
}
