<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when a product is not found
 */
final class ProductNotFoundException extends EcommerceException
{
    public function __construct(int|string $productIdentifier)
    {
        $message = is_int($productIdentifier)
            ? "Product with ID {$productIdentifier} not found"
            : "Product with identifier {$productIdentifier} not found";

        parent::__construct($message, 404);
    }
}
