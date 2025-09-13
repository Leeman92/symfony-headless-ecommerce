<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Interface for entities that support validation
 * 
 * Provides methods for validating entity state and
 * retrieving validation errors.
 */
interface ValidatableInterface
{
    /**
     * Validate the entity and return any constraint violations
     */
    public function validate(): ConstraintViolationListInterface;

    /**
     * Check if the entity is valid
     */
    public function isValid(): bool;

    /**
     * Get validation errors as an array
     * 
     * @return array<string, string[]>
     */
    public function getValidationErrors(): array;
}