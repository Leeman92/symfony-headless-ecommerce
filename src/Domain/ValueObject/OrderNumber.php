<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;
use Random\RandomException;

use function preg_match;
use function random_int;
use function str_pad;
use function strlen;
use function strtoupper;
use function substr;
use function time;
use function trim;

use const STR_PAD_LEFT;

/**
 * Order number value object
 *
 * Encapsulates order number generation and validation according to DDD principles.
 * Ensures order numbers are always in a valid format.
 */
final readonly class OrderNumber
{
    private string $value;

    public function __construct(string $orderNumber)
    {
        $orderNumber = trim(strtoupper($orderNumber));

        if ($orderNumber === '') {
            throw new InvalidArgumentException('Order number cannot be empty');
        }

        if (strlen($orderNumber) > 20) {
            throw new InvalidArgumentException('Order number cannot be longer than 20 characters');
        }

        if (preg_match('/^[A-Z0-9\-]+$/', $orderNumber) !== 1) {
            throw new InvalidArgumentException('Order number can only contain uppercase letters, numbers, and hyphens');
        }

        $this->value = $orderNumber;
    }

    /**
     * @throws RandomException
     */
    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(10)));
    }

    public static function generateWithPrefix(string $prefix): self
    {
        $timestamp = time();
        $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $orderNumber = "{$prefix}-{$timestamp}-{$random}";

        // Ensure the generated order number doesn't exceed 20 characters
        if (strlen($orderNumber) > 20) {
            // Use shorter timestamp (last 8 digits) and smaller random
            $shortTimestamp = substr((string) $timestamp, -8);
            $shortRandom = str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
            $orderNumber = "{$prefix}-{$shortTimestamp}-{$shortRandom}";

            // If still too long, truncate prefix
            if (strlen($orderNumber) > 20) {
                $maxPrefixLength = 20 - strlen("-{$shortTimestamp}-{$shortRandom}");
                $truncatedPrefix = substr($prefix, 0, $maxPrefixLength);
                $orderNumber = "{$truncatedPrefix}-{$shortTimestamp}-{$shortRandom}";
            }
        }

        return new self($orderNumber);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getYear(): ?string
    {
        if (preg_match('/^ORD-(\d{4})\d{4}-\d{3}$/', $this->value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function getDate(): ?string
    {
        if (preg_match('/^ORD-(\d{4})(\d{2})(\d{2})-\d{3}$/', $this->value, $matches) === 1) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        return null;
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
