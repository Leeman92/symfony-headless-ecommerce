<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Interface for entities that can be identified by various means
 * 
 * Extends the basic EntityInterface to provide additional
 * identification methods for complex entity relationships.
 */
interface IdentifiableInterface extends EntityInterface
{
    /**
     * Get a unique identifier for the entity
     * This could be ID, UUID, slug, or other unique field
     */
    public function getIdentifier(): string|int|null;

    /**
     * Check if entity has a valid identifier
     */
    public function hasIdentifier(): bool;
}