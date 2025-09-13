<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Phone number value object
 * 
 * Encapsulates phone number validation and formatting according to DDD principles.
 * Ensures phone numbers are always in a valid format.
 */
final readonly class Phone
{
    private string $value;

    public function __construct(string $phone)
    {
        $phone = $this->normalizePhone($phone);
        
        if (empty($phone)) {
            throw new InvalidArgumentException('Phone number cannot be empty');
        }

        if (strlen($phone) < 10 || strlen($phone) > 20) {
            throw new InvalidArgumentException('Phone number must be between 10 and 20 characters');
        }

        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            throw new InvalidArgumentException('Invalid phone number format');
        }

        $this->value = $phone;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountryCode(): ?string
    {
        if (str_starts_with($this->value, '+')) {
            // Extract country code (simplified - real implementation would use a proper library)
            if (str_starts_with($this->value, '+1')) {
                return '+1';
            }
            if (str_starts_with($this->value, '+44')) {
                return '+44';
            }
            if (str_starts_with($this->value, '+49')) {
                return '+49';
            }
            // Add more country codes as needed
        }
        
        return null;
    }

    public function getFormattedForCountry(string $countryCode = 'US'): string
    {
        return match ($countryCode) {
            'US' => $this->formatAsUS(),
            'UK' => $this->formatAsUK(),
            'DE' => $this->formatAsDE(),
            default => $this->value,
        };
    }

    public function equals(Phone $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure it starts with + if it's an international number
        if (strlen($normalized) > 10 && !str_starts_with($normalized, '+')) {
            $normalized = '+' . $normalized;
        }
        
        return $normalized;
    }

    private function formatAsUS(): string
    {
        $digits = preg_replace('/[^\d]/', '', $this->value);
        
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4)
            );
        }
        
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return sprintf('+1 (%s) %s-%s', 
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 4)
            );
        }
        
        return $this->value;
    }

    private function formatAsUK(): string
    {
        // Simplified UK formatting
        return $this->value;
    }

    private function formatAsDE(): string
    {
        // Simplified German formatting
        return $this->value;
    }
}