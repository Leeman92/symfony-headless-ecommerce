<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract base entity providing common functionality
 * 
 * All domain entities should extend this class to ensure
 * consistent ID handling and basic entity behavior.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractEntity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Check if entity is persisted (has an ID)
     */
    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    /**
     * Check if entity is new (not persisted)
     */
    public function isNew(): bool
    {
        return $this->id === null;
    }

    /**
     * Compare entities by ID
     */
    public function equals(EntityInterface $other): bool
    {
        if (!$other instanceof static) {
            return false;
        }

        if ($this->isNew() || $other->isNew()) {
            return false;
        }

        return $this->getId() === $other->getId();
    }
}