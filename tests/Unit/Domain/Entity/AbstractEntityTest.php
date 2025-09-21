<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\AbstractEntity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for AbstractEntity
 */
final class AbstractEntityTest extends TestCase
{
    private TestEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new TestEntity();
    }

    public function testNewEntityHasNoId(): void
    {
        self::assertNull($this->entity->getId());
    }

    public function testIsNewReturnsTrueForNewEntity(): void
    {
        self::assertTrue($this->entity->isNew());
    }

    public function testIsPersistedReturnsFalseForNewEntity(): void
    {
        self::assertFalse($this->entity->isPersisted());
    }

    public function testIsPersistedReturnsTrueForEntityWithId(): void
    {
        $this->setEntityId($this->entity, 123);

        self::assertTrue($this->entity->isPersisted());
        self::assertFalse($this->entity->isNew());
        self::assertSame(123, $this->entity->getId());
    }

    public function testEqualsReturnsTrueForSameEntity(): void
    {
        $entity1 = new TestEntity();
        $entity2 = new TestEntity();

        $this->setEntityId($entity1, 123);
        $this->setEntityId($entity2, 123);

        self::assertTrue($entity1->equals($entity2));
    }

    public function testEqualsReturnsFalseForDifferentEntities(): void
    {
        $entity1 = new TestEntity();
        $entity2 = new TestEntity();

        $this->setEntityId($entity1, 123);
        $this->setEntityId($entity2, 456);

        self::assertFalse($entity1->equals($entity2));
    }

    public function testEqualsReturnsFalseForNewEntities(): void
    {
        $entity1 = new TestEntity();
        $entity2 = new TestEntity();

        self::assertFalse($entity1->equals($entity2));
    }

    public function testEqualsReturnsFalseForDifferentEntityTypes(): void
    {
        $entity1 = new TestEntity();
        $entity2 = new AnotherTestEntity();

        $this->setEntityId($entity1, 123);
        $this->setEntityId($entity2, 123);

        self::assertFalse($entity1->equals($entity2));
    }

    private function setEntityId(AbstractEntity $entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}

/**
 * Test entity for testing AbstractEntity functionality
 */
class TestEntity extends AbstractEntity
{
}

/**
 * Another test entity for testing type comparison
 */
class AnotherTestEntity extends AbstractEntity
{
}
