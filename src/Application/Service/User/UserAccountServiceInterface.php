<?php

declare(strict_types=1);

namespace App\Application\Service\User;

use App\Domain\Entity\Order;
use App\Domain\Entity\User;

interface UserAccountServiceInterface
{
    public function register(UserRegistrationData $data): User;

    public function convertGuestOrderToUserAccount(Order $order, GuestAccountData $data): User;
}
