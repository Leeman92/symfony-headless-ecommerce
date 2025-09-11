<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when a product is not found
 */
final class ProductNotFoundException extends EcommerceException
{
    public function __construct(int $productId)
    {
        parent::__construct("Product with ID {$productId} not found", 404);
    }
}