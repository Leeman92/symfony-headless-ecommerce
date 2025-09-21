<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\IdentifiableInterface;
use App\Domain\Entity\IdentifiableTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IdentifiableTrait
 */
final class IdentifiableTraitTest extends TestCase
{
    private IdentifiableTestEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new IdentifiableTestEntity();
    }

    public function testGetIdentifierReturnsId(): void
    {
        $this->entity->setId(123);

        self::assertSame(123, $this->entity->getIdentifier());
    }

    public function testGetIdentifierReturnsNullForNewEntity(): void
    {
        self::assertNull($this->entity->getIdentifier());
    }

    public function testHasIdentifierReturnsFalseForNewEntity(): void
    {
        self::assertFalse($this->entity->hasIdentifier());
    }

    public function testHasIdentifierReturnsTrueForEntityWithId(): void
    {
        $this->entity->setId(123);

        self::assertTrue($this->entity->hasIdentifier());
    }
}

/**
 * Test entity for testing IdentifiableTrait functionality
 */
class IdentifiableTestEntity implements IdentifiableInterface
{
    use IdentifiableTrait;

    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
