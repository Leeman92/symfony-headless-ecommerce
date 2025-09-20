<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\OrderNumber;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function findByOrderNumber(OrderNumber|string $orderNumber): ?Order;

    /** @return list<Order> */
    public function findRecentOrdersForUser(User $user, int $limit = 10): array;

    /** @return list<Order> */
    public function findOrdersForGuestEmail(Email|string $email, int $limit = 10): array;

    /** @return list<Order> */
    public function findOpenOrders(): array;
}
