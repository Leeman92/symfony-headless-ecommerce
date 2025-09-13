<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Person name value object
 * 
 * Encapsulates person name validation and behavior according to DDD principles.
 * Ensures names are always in a valid state.
 */
final readonly class PersonName
{
    private string $firstName;
    private string $lastName;

    public function __construct(string $firstName, string $lastName)
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if (empty($firstName)) {
            throw new InvalidArgumentException('First name cannot be empty');
        }

        if (empty($lastName)) {
            throw new InvalidArgumentException('Last name cannot be empty');
        }

        if (strlen($firstName) < 2 || strlen($firstName) > 100) {
            throw new InvalidArgumentException('First name must be between 2 and 100 characters');
        }

        if (strlen($lastName) < 2 || strlen($lastName) > 100) {
            throw new InvalidArgumentException('Last name must be between 2 and 100 characters');
        }

        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getInitials(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }

    public function equals(PersonName $other): bool
    {
        return $this->firstName === $other->firstName 
            && $this->lastName === $other->lastName;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}