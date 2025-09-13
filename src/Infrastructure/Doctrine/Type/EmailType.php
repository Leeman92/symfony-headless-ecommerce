<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Custom Doctrine type for Email value object
 */
final class EmailType extends StringType
{
    public const NAME = 'email';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new Email($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Email) {
            throw new \InvalidArgumentException('Expected Email value object');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}