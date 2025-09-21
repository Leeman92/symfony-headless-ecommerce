<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Base interface for all domain entities
 */
interface EntityInterface
{
    public function getId(): ?int;
}
