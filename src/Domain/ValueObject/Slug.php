<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * URL slug value object
 * 
 * Encapsulates slug validation and generation according to DDD principles.
 * Ensures slugs are always URL-safe and SEO-friendly.
 */
final readonly class Slug
{
    private string $value;

    public function __construct(string $slug)
    {
        $slug = trim(strtolower($slug));
        
        if (empty($slug)) {
            throw new InvalidArgumentException('Slug cannot be empty');
        }

        if (strlen($slug) > 220) {
            throw new InvalidArgumentException('Slug cannot be longer than 220 characters');
        }

        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            throw new InvalidArgumentException('Slug can only contain lowercase letters, numbers, and hyphens');
        }

        if (str_starts_with($slug, '-') || str_ends_with($slug, '-')) {
            throw new InvalidArgumentException('Slug cannot start or end with a hyphen');
        }

        if (str_contains($slug, '--')) {
            throw new InvalidArgumentException('Slug cannot contain consecutive hyphens');
        }

        $this->value = $slug;
    }

    public static function fromString(string $text): self
    {
        // Convert text to URL-friendly slug
        $slug = strtolower(trim($text));
        
        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // Truncate if too long
        if (strlen($slug) > 220) {
            $slug = substr($slug, 0, 220);
            $slug = rtrim($slug, '-');
        }
        
        if (empty($slug)) {
            throw new InvalidArgumentException('Cannot generate slug from provided text');
        }
        
        return new self($slug);
    }

    public static function fromStringWithSuffix(string $text, string $suffix): self
    {
        $baseSlug = self::fromString($text);
        $maxLength = 220 - strlen($suffix) - 1; // -1 for the hyphen
        
        $truncatedSlug = substr($baseSlug->value, 0, $maxLength);
        $truncatedSlug = rtrim($truncatedSlug, '-');
        
        return new self($truncatedSlug . '-' . $suffix);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function withSuffix(string $suffix): self
    {
        return new self($this->value . '-' . $suffix);
    }

    public function withPrefix(string $prefix): self
    {
        return new self($prefix . '-' . $this->value);
    }

    public function equals(Slug $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}