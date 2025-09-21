<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when order data is invalid
 */
final class InvalidOrderDataException extends EcommerceException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid order data: {$reason}", 400);
    }
}
