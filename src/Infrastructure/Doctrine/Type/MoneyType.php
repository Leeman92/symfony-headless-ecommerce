<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use JsonException;

use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Custom Doctrine type for Money value object
 *
 * Stores Money as JSON with amount and currency
 */
final class MoneyType extends Type
{
    public const NAME = 'money';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Money JSON is malformed', 0, $exception);
        }

        if (!is_array($decoded) || !isset($decoded['amount'], $decoded['currency'])) {
            return null;
        }

        $amount = $decoded['amount'];
        $currency = $decoded['currency'];

        if (!is_string($currency) || (!is_string($amount) && !is_numeric($amount))) {
            return null;
        }

        return new Money((string) $amount, $currency);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new InvalidArgumentException('Expected Money value object');
        }

        try {
            $encoded = json_encode([
                'amount' => $value->getAmount(),
                'currency' => $value->getCurrency(),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to encode Money to JSON', 0, $exception);
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
