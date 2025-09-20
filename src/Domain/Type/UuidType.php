<?php

declare(strict_types=1);

namespace App\Domain\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

/**
 * Custom Doctrine type for PostgreSQL UUID columns
 * 
 * Provides native UUID support for PostgreSQL with proper
 * indexing and performance optimizations.
 */
final class UuidType extends GuidType
{
    public const NAME = 'uuid';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform->getName() === 'postgresql') {
            return 'UUID';
        }

        return parent::getSQLDeclaration($column, $platform);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
