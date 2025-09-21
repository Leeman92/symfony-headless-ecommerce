<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Phone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

/**
 * Custom Doctrine type for Phone value object
 */
final class PhoneType extends StringType
{
    public const NAME = 'phone';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Phone
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new Phone($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Phone) {
            throw new InvalidArgumentException('Expected Phone value object');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
