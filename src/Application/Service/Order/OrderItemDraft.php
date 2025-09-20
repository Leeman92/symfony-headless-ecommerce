<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Domain\ValueObject\Money;
use InvalidArgumentException;

final class OrderItemDraft
{
    private int $productId;

    private int $quantity;

    private ?Money $unitPriceOverride;

    public function __construct(int $productId, int $quantity, ?Money $unitPriceOverride = null)
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('Product ID must be positive');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPriceOverride = $unitPriceOverride;
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPriceOverride(): ?Money
    {
        return $this->unitPriceOverride;
    }
}
