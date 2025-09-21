<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function strlen;

/**
 * Money value object
 *
 * Encapsulates monetary amounts with currency according to DDD principles.
 * Ensures monetary calculations are always precise and currency-aware.
 */
final readonly class Money
{
    public const DEFAULT_CURRENCY = 'USD';

    private string $amount;
    private string $currency;

    public function __construct(string $amount, string $currency = self::DEFAULT_CURRENCY)
    {
        $this->validateAmount($amount);
        $this->validateCurrency($currency);

        $this->amount = $this->normalizeAmount($amount);
        $this->currency = strtoupper($currency);
    }

    public static function fromFloat(float $amount, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(number_format($amount, 2, '.', ''), $currency);
    }

    public static function fromCents(int $cents, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(number_format($cents / 100, 2, '.', ''), $currency);
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self('0.00', $currency);
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountAsFloat(): float
    {
        return (float) $this->amount;
    }

    public function getAmountInCents(): int
    {
        return (int) round($this->getAmountAsFloat() * 100);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        $newAmount = $this->getAmountAsFloat() + $other->getAmountAsFloat();

        return self::fromFloat($newAmount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        $newAmount = $this->getAmountAsFloat() - $other->getAmountAsFloat();
        if ($newAmount < 0) {
            throw new InvalidArgumentException('Cannot subtract to negative amount');
        }

        return self::fromFloat($newAmount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        $newAmount = $this->getAmountAsFloat() * $multiplier;

        return self::fromFloat($newAmount, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->getAmountAsFloat() === 0.0;
    }

    public function isPositive(): bool
    {
        return $this->getAmountAsFloat() > 0.0;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->getAmountAsFloat() > $other->getAmountAsFloat();
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->getAmountAsFloat() < $other->getAmountAsFloat();
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return match ($this->currency) {
            'USD' => '$'.$this->amount,
            'EUR' => '€'.$this->amount,
            'GBP' => '£'.$this->amount,
            default => $this->amount.' '.$this->currency,
        };
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function validateAmount(string $amount): void
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Amount must be numeric');
        }

        if ((float) $amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    private function validateCurrency(string $currency): void
    {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be 3 characters long');
        }

        if (!ctype_alpha($currency)) {
            throw new InvalidArgumentException('Currency must contain only letters');
        }
    }

    private function normalizeAmount(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot perform operation on different currencies: {$this->currency} and {$other->currency}");
        }
    }
}
