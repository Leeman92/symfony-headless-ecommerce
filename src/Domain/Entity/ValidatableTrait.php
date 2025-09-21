<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Stringable;
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

    /**
     * @return array<string, list<string|Stringable>>
     */
    public function getValidationErrors(): array
    {
        $violations = $this->validate();
        /** @var array<string, list<string>> $errors */
        $errors = [];

        foreach ($violations as $violation) {
            $property = $violation->getPropertyPath();
            $errors[$property][] = $violation->getMessage();
        }

        return $errors;
    }
}
