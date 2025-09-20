<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function findByStripePaymentIntentId(string $paymentIntentId): ?Payment;

    public function findOneByOrder(Order $order): ?Payment;
}
