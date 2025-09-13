<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Email value object
 * 
 * Encapsulates email validation and behavior according to DDD principles.
 * Ensures emails are always in a valid state.
 */
final readonly class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $email = trim(strtolower($email));
        
        if (empty($email)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        if (strlen($email) > 180) {
            throw new InvalidArgumentException('Email cannot be longer than 180 characters');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        $this->value = $email;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}