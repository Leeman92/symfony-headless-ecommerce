<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function filter_var;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

use const FILTER_VALIDATE_EMAIL;

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

        if ($email === '') {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        if (strlen($email) > 180) {
            throw new InvalidArgumentException('Email cannot be longer than 180 characters');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
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
        $separatorPosition = strpos($this->value, '@');

        return $separatorPosition === false ? '' : substr($this->value, $separatorPosition + 1);
    }

    public function getLocalPart(): string
    {
        $separatorPosition = strpos($this->value, '@');

        return $separatorPosition === false ? '' : substr($this->value, 0, $separatorPosition);
    }

    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
