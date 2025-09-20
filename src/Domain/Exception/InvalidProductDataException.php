<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when product data fails validation checks
 */
final class InvalidProductDataException extends EcommerceException
{
    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct('Invalid product data provided', 422);
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
