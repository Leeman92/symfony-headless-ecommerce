<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\Repository\PaymentRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

final class PaymentRepository extends AbstractRepository implements PaymentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByStripePaymentIntentId(string $paymentIntentId): ?Payment
    {
        return $this->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
    }

    public function findOneByOrder(Order $order): ?Payment
    {
        return $this->findOneBy(['order' => $order]);
    }
}
