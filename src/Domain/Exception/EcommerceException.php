<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;
use Throwable;

/**
 * Base exception for all e-commerce domain exceptions
 */
abstract class EcommerceException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
