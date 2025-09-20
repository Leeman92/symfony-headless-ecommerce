<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Domain\Entity\Order;
use App\Domain\Entity\User;

interface OrderServiceInterface
{
    public function createGuestOrder(OrderDraft $orderDraft, GuestCustomerData $guestCustomer): Order;

    public function createUserOrder(User $user, OrderDraft $orderDraft): Order;

    public function convertGuestOrderToUser(Order $order, User $user): Order;
}
