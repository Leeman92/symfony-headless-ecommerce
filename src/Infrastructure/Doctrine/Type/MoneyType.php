<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

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

        $data = json_decode($value, true);
        
        if (!is_array($data) || !isset($data['amount'], $data['currency'])) {
            return null;
        }

        return new Money($data['amount'], $data['currency']);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new \InvalidArgumentException('Expected Money value object');
        }

        return json_encode([
            'amount' => $value->getAmount(),
            'currency' => $value->getCurrency(),
        ]);
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