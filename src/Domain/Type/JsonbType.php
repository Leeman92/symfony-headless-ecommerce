<?php

declare(strict_types=1);

namespace App\Domain\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

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
        return 'JSONB';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}