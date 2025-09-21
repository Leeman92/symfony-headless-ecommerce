<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function count;
use function explode;
use function preg_match;
use function preg_replace;
use function random_int;
use function str_pad;
use function strlen;
use function strtoupper;
use function substr;
use function time;
use function trim;

use const STR_PAD_LEFT;

/**
 * Product SKU value object
 *
 * Encapsulates SKU validation and formatting according to DDD principles.
 * Ensures SKUs are always in a valid format.
 */
final readonly class ProductSku
{
    private string $value;

    public function __construct(string $sku)
    {
        $sku = trim(strtoupper($sku));

        if ($sku === '') {
            throw new InvalidArgumentException('SKU cannot be empty');
        }

        if (strlen($sku) > 50) {
            throw new InvalidArgumentException('SKU cannot be longer than 50 characters');
        }

        if (preg_match('/^[A-Z0-9\-_]+$/', $sku) !== 1) {
            throw new InvalidArgumentException('SKU can only contain uppercase letters, numbers, hyphens, and underscores');
        }

        $this->value = $sku;
    }

    public static function generate(string $prefix = 'SKU'): self
    {
        $timestamp = time();
        $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return new self("{$prefix}-{$timestamp}-{$random}");
    }

    public static function fromProductName(string $productName): self
    {
        // Generate SKU from product name
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '-', $productName) ?? '';
        $normalized = strtoupper($normalized);
        $normalized = preg_replace('/-+/', '-', $normalized) ?? $normalized;
        $normalized = trim($normalized, '-');

        if (strlen($normalized) > 30) {
            $normalized = substr($normalized, 0, 30);
        }

        $random = str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

        return new self("{$normalized}-{$random}");
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getPrefix(): ?string
    {
        $parts = explode('-', $this->value);

        return count($parts) > 1 ? $parts[0] : null;
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
