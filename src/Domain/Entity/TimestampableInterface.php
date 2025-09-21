<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeInterface;

/**
 * Interface for entities that track creation and update timestamps
 */
interface TimestampableInterface
{
    public function getCreatedAt(): ?DateTimeInterface;

    public function setCreatedAt(DateTimeInterface $createdAt): static;

    public function getUpdatedAt(): ?DateTimeInterface;

    public function setUpdatedAt(DateTimeInterface $updatedAt): static;
}
