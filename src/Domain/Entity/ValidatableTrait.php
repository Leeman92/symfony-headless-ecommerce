<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Trait providing validation functionality for entities
 * 
 * Uses Symfony Validator component to validate entity state
 * based on validation constraints defined in the entity.
 */
trait ValidatableTrait
{
    public function validate(): ConstraintViolationListInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return $validator->validate($this);
    }

    public function isValid(): bool
    {
        return $this->validate()->count() === 0;
    }

    public function getValidationErrors(): array
    {
        $violations = $this->validate();
        $errors = [];

        foreach ($violations as $violation) {
            $property = $violation->getPropertyPath();
            if (!isset($errors[$property])) {
                $errors[$property] = [];
            }
            $errors[$property][] = $violation->getMessage();
        }

        return $errors;
    }
}