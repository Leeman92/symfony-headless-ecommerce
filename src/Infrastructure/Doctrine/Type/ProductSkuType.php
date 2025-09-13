<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\ProductSku;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Custom Doctrine type for ProductSku value object
 */
final class ProductSkuType extends StringType
{
    public const NAME = 'product_sku';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductSku
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new ProductSku($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof ProductSku) {
            throw new \InvalidArgumentException('Expected ProductSku value object');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}