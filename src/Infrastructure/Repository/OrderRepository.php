<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\OrderNumber;
use Doctrine\Persistence\ManagerRegistry;

final class OrderRepository extends AbstractRepository implements OrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByOrderNumber(OrderNumber|string $orderNumber): ?Order
    {
        $value = $orderNumber instanceof OrderNumber ? $orderNumber->getValue() : (string) $orderNumber;

        return $this->createQueryBuilder('orderRecord')
            ->andWhere('orderRecord.orderNumber = :orderNumber')
            ->setParameter('orderNumber', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentOrdersForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('orderRecord')
            ->andWhere('orderRecord.customer = :customer')
            ->setParameter('customer', $user)
            ->orderBy('orderRecord.id', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findOrdersForGuestEmail(Email|string $email, int $limit = 10): array
    {
        $value = $email instanceof Email ? $email->getValue() : (string) $email;

        return $this->createQueryBuilder('orderRecord')
            ->andWhere('orderRecord.guestEmail = :guestEmail')
            ->setParameter('guestEmail', $value)
            ->orderBy('orderRecord.id', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findOpenOrders(): array
    {
        $openStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
        ];

        return $this->createQueryBuilder('orderRecord')
            ->andWhere('orderRecord.status IN (:statuses)')
            ->setParameter('statuses', $openStatuses)
            ->orderBy('orderRecord.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
