<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Address;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Custom Doctrine type for Address value object
 * 
 * Stores Address as JSON with all address components
 */
final class AddressType extends Type
{
    public const NAME = 'address';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Address
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);
        
        if (!is_array($data)) {
            return null;
        }

        return Address::fromArray($data);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Address) {
            throw new \InvalidArgumentException('Expected Address value object');
        }

        return json_encode($value->toArray());
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}