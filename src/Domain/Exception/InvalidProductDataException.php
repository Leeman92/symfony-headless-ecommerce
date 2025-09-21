<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Stringable;

/**
 * Exception thrown when product data fails validation checks
 */
final class InvalidProductDataException extends EcommerceException
{
    /**
     * @param array<string, list<string|Stringable>> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Invalid product data provided', 422);
    }

    /**
     * @return array<string, list<string|Stringable>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
