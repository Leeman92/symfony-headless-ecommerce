<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when there's insufficient stock for a product
 */
final class InsufficientStockException extends EcommerceException
{
    public function __construct(int $productId, int $requestedQuantity, int $availableStock)
    {
        parent::__construct(
            "Insufficient stock for product {$productId}. Requested: {$requestedQuantity}, Available: {$availableStock}",
            400,
        );
    }
}
