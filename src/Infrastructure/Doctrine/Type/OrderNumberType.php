<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\OrderNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Custom Doctrine type for OrderNumber value object
 */
final class OrderNumberType extends StringType
{
    public const NAME = 'order_number';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderNumber
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new OrderNumber($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OrderNumber) {
            throw new \InvalidArgumentException('Expected OrderNumber value object');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}