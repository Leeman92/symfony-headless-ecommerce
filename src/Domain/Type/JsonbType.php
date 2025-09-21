<?php

declare(strict_types=1);

namespace App\Domain\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\JsonType;
use Throwable;

/**
 * Custom Doctrine type for PostgreSQL JSONB columns
 *
 * Provides enhanced JSON support with indexing capabilities
 * for PostgreSQL JSONB data type.
 */
final class JsonbType extends JsonType
{
    public const NAME = 'jsonb';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // Use native JSONB on PostgreSQL, otherwise fall back to the platform JSON declaration
        if ($platform instanceof PostgreSQLPlatform) {
            return 'JSONB';
        }

        try {
            return $platform->getJsonTypeDeclarationSQL($column);
        } catch (Throwable) {
            // SQLite and other lightweight platforms map to CLOB via the generic declaration
            return parent::getSQLDeclaration($column, $platform);
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
