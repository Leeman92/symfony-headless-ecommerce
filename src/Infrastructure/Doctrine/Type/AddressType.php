<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Address;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use JsonException;

use function is_array;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

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

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Address JSON is malformed', 0, $exception);
        }

        if (!is_array($decoded)) {
            return null;
        }

        return Address::fromArray($decoded);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Address) {
            throw new InvalidArgumentException('Expected Address value object');
        }

        try {
            $encoded = json_encode($value->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to encode Address to JSON', 0, $exception);
        }

        return $encoded;
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
