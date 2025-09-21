<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\OrderNumber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Unit tests for OrderNumber value object
 */
final class OrderNumberTest extends TestCase
{
    public function testOrderNumberCreation(): void
    {
        $orderNumber = new OrderNumber('ORD-20241213-001');

        self::assertSame('ORD-20241213-001', $orderNumber->getValue());
        self::assertSame('ORD-20241213-001', (string) $orderNumber);
    }

    public function testOrderNumberNormalization(): void
    {
        $orderNumber = new OrderNumber('  ord-20241213-001  ');

        self::assertSame('ORD-20241213-001', $orderNumber->getValue());
    }

    public function testOrderNumberGeneration(): void
    {
        $orderNumber = OrderNumber::generate();

        self::assertMatchesRegularExpression('/^[A-Z0-9]{20}$/', $orderNumber->getValue());
    }

    public function testOrderNumberGenerationWithPrefix(): void
    {
        $orderNumber = OrderNumber::generateWithPrefix('CUSTOM');

        self::assertStringStartsWith('CUSTOM-', $orderNumber->getValue());
        self::assertMatchesRegularExpression('/^CUSTOM-\d+-\d{2,4}$/', $orderNumber->getValue());
        self::assertLessThanOrEqual(20, strlen($orderNumber->getValue()));
    }

    public function testOrderNumberYearExtraction(): void
    {
        $orderNumber = new OrderNumber('ORD-20241213-001');

        self::assertSame('2024', $orderNumber->getYear());
    }

    public function testOrderNumberDateExtraction(): void
    {
        $orderNumber = new OrderNumber('ORD-20241213-001');

        self::assertSame('2024-12-13', $orderNumber->getDate());
    }

    public function testOrderNumberWithoutDateFormat(): void
    {
        $orderNumber = new OrderNumber('CUSTOM-123456-001');

        self::assertNull($orderNumber->getYear());
        self::assertNull($orderNumber->getDate());
    }

    public function testOrderNumberEquality(): void
    {
        $orderNumber1 = new OrderNumber('ORD-20241213-001');
        $orderNumber2 = new OrderNumber('ORD-20241213-001');
        $orderNumber3 = new OrderNumber('ORD-20241213-002');

        self::assertTrue($orderNumber1->equals($orderNumber2));
        self::assertFalse($orderNumber1->equals($orderNumber3));
    }

    public function testEmptyOrderNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order number cannot be empty');

        new OrderNumber('');
    }

    public function testOrderNumberTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order number cannot be longer than 20 characters');

        $longOrderNumber = str_repeat('A', 21);
        new OrderNumber($longOrderNumber);
    }

    public function testInvalidOrderNumberFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order number can only contain uppercase letters, numbers, and hyphens');

        new OrderNumber('ORD@123');
    }

    public function testOrderNumberUniqueness(): void
    {
        $orderNumber1 = OrderNumber::generate();
        $orderNumber2 = OrderNumber::generate();

        // Should be different (very high probability)
        self::assertNotEquals($orderNumber1->getValue(), $orderNumber2->getValue());
    }
}
