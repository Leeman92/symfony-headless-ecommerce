<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Trait providing default identifiable functionality
 * 
 * Uses the entity ID as the default identifier.
 * Can be overridden in specific entities for custom identification.
 */
trait IdentifiableTrait
{
    public function getIdentifier(): string|int|null
    {
        return $this->getId();
    }

    public function hasIdentifier(): bool
    {
        return $this->getIdentifier() !== null;
    }
}