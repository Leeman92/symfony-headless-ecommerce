<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Slug;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

use function is_string;

/**
 * Custom Doctrine type for Slug value object
 */
final class SlugType extends StringType
{
    public const NAME = 'slug';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Slug
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new Slug($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = new Slug($value);
        }

        if (!$value instanceof Slug) {
            throw new InvalidArgumentException('Expected Slug value object or string');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
