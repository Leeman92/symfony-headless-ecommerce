<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\TimestampableInterface;
use App\Domain\Entity\TimestampableTrait;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TimestampableTrait
 */
final class TimestampableTraitTest extends TestCase
{
    private TimestampableTestEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new TimestampableTestEntity();
    }

    public function testNewEntityHasNoTimestamps(): void
    {
        self::assertNull($this->entity->getCreatedAt());
        self::assertNull($this->entity->getUpdatedAt());
    }

    public function testSetCreatedAt(): void
    {
        $date = new DateTime('2024-01-01 12:00:00');
        $result = $this->entity->setCreatedAt($date);

        self::assertSame($this->entity, $result);
        self::assertSame($date, $this->entity->getCreatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $date = new DateTime('2024-01-01 12:00:00');
        $result = $this->entity->setUpdatedAt($date);

        self::assertSame($this->entity, $result);
        self::assertSame($date, $this->entity->getUpdatedAt());
    }

    public function testSetCreatedAtValueSetsTimestamps(): void
    {
        $beforeCall = new DateTime();
        $this->entity->setCreatedAtValue();
        $afterCall = new DateTime();

        self::assertNotNull($this->entity->getCreatedAt());
        self::assertNotNull($this->entity->getUpdatedAt());
        
        // Check that timestamps are within reasonable range
        self::assertGreaterThanOrEqual($beforeCall, $this->entity->getCreatedAt());
        self::assertLessThanOrEqual($afterCall, $this->entity->getCreatedAt());
        
        self::assertGreaterThanOrEqual($beforeCall, $this->entity->getUpdatedAt());
        self::assertLessThanOrEqual($afterCall, $this->entity->getUpdatedAt());
    }

    public function testSetUpdatedAtValueUpdatesOnlyUpdatedAt(): void
    {
        $originalCreated = new DateTime('2024-01-01 12:00:00');
        $this->entity->setCreatedAt($originalCreated);

        $beforeCall = new DateTime();
        $this->entity->setUpdatedAtValue();
        $afterCall = new DateTime();

        // Created at should remain unchanged
        self::assertSame($originalCreated, $this->entity->getCreatedAt());
        
        // Updated at should be set to current time
        self::assertNotNull($this->entity->getUpdatedAt());
        self::assertGreaterThanOrEqual($beforeCall, $this->entity->getUpdatedAt());
        self::assertLessThanOrEqual($afterCall, $this->entity->getUpdatedAt());
    }
}

/**
 * Test entity for testing TimestampableTrait functionality
 */
class TimestampableTestEntity implements TimestampableInterface
{
    use TimestampableTrait;
}