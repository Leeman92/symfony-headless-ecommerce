<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

/**
 * Exception thrown when payment processing fails
 */
final class PaymentProcessingException extends EcommerceException
{
    public function __construct(string $reason, ?Throwable $previous = null)
    {
        parent::__construct("Payment processing failed: {$reason}", 500, $previous);
    }
}
