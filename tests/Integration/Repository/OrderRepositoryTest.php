<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Category;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\Payment;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Domain\ValueObject\PersonName;
use App\Infrastructure\Repository\OrderRepository;
use App\Tests\Support\Doctrine\DoctrineRepositoryTestCase;
use DateTimeImmutable;
use http\Exception\RuntimeException;

use function random_int;

final class OrderRepositoryTest extends DoctrineRepositoryTestCase
{
    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        if (null === $this->managerRegistry) {
            throw new RuntimeException('ManagerRegistry cannot be null');
        }

        $this->repository = new OrderRepository($this->managerRegistry);
    }

    protected function schemaClasses(): array
    {
        return [
            Category::class,
            Product::class,
            User::class,
            Order::class,
            OrderItem::class,
            Payment::class,
        ];
    }

    public function testFindByOrderNumberReturnsOrder(): void
    {
        $user = $this->createUser('buyer@example.com');
        $order = $this->createOrder('ORD-1001', $user);

        $this->entityManager?->flush();

        $found = $this->repository->findByOrderNumber(new OrderNumber('ORD-1001'));

        self::assertNotNull($found);
        self::assertSame($order->getId(), $found->getId());
    }

    public function testFindRecentOrdersForUserReturnsMostRecent(): void
    {
        $user = $this->createUser('buyer@example.com');
        $this->createOrder('ORD-1001', $user, new DateTimeImmutable('2024-01-01 10:00:00'));
        $recent = $this->createOrder('ORD-1002', $user, new DateTimeImmutable('2024-01-05 12:00:00'));

        $this->entityManager?->flush();

        $results = $this->repository->findRecentOrdersForUser($user, 1);

        self::assertCount(1, $results);
        self::assertSame($recent->getId(), $results[0]->getId());
    }

    public function testFindOrdersForGuestEmailReturnsOrders(): void
    {
        $this->createGuestOrder('guest@example.com');
        $this->createGuestOrder('guest@example.com');
        $this->createGuestOrder('other@example.com');

        $results = $this->repository->findOrdersForGuestEmail('guest@example.com', 5);

        self::assertCount(2, $results);
        foreach ($results as $order) {
            self::assertTrue($order->isGuestOrder());
        }
    }

    public function testFindOpenOrdersExcludesCompletedStatuses(): void
    {
        $user = $this->createUser('buyer@example.com');
        $open = $this->createOrder('ORD-2001', $user);
        $completed = $this->createOrder('ORD-2002', $user);
        $completed->setStatus(Order::STATUS_DELIVERED);
        $this->entityManager?->flush();

        $results = $this->repository->findOpenOrders();

        self::assertCount(1, $results);
        self::assertSame($open->getId(), $results[0]->getId());
    }

    private function createUser(string $email): User
    {
        $user = new User(new Email($email), new PersonName('Test', 'User'), '', 'hash');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager?->persist($user);
        $this->entityManager?->flush();

        return $user;
    }

    private function createOrder(
        string $orderNumber,
        ?User $user = null,
        ?DateTimeImmutable $createdAt = null,
    ): Order {
        $order = new Order(new OrderNumber($orderNumber));

        if ($user !== null) {
            $order->setCustomer($user);
        }

        $order->setSubtotal(new Money('100.00'))
            ->setTaxAmount(new Money('10.00'))
            ->setShippingAmount(new Money('5.00'))
            ->setDiscountAmount(Money::zero())
            ->calculateTotal()
            ->setStatus(Order::STATUS_PENDING);

        if ($createdAt !== null) {
            $order->setCreatedAt($createdAt);
            $order->setUpdatedAt($createdAt);
        }

        $this->entityManager?->persist($order);

        return $order;
    }

    private function createGuestOrder(string $email): Order
    {
        $order = $this->createOrder('ORD-'.random_int(3000, 3999));
        $order->setCustomer(null);
        $order->setGuestEmail(new Email($email));
        $order->setGuestName(new PersonName('Guest', 'Customer'));
        $this->entityManager?->flush();

        return $order;
    }
}
