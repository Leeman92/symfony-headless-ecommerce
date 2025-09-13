<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Money value object
 */
final class MoneyTest extends TestCase
{
    public function testMoneyCreation(): void
    {
        $money = new Money('99.99', 'USD');
        
        self::assertSame('99.99', $money->getAmount());
        self::assertSame(99.99, $money->getAmountAsFloat());
        self::assertSame(9999, $money->getAmountInCents());
        self::assertSame('USD', $money->getCurrency());
        self::assertSame('$99.99', $money->format());
    }

    public function testMoneyFromFloat(): void
    {
        $money = Money::fromFloat(123.456, 'EUR');
        
        self::assertSame('123.46', $money->getAmount());
        self::assertSame('EUR', $money->getCurrency());
    }

    public function testMoneyFromCents(): void
    {
        $money = Money::fromCents(12345, 'GBP');
        
        self::assertSame('123.45', $money->getAmount());
        self::assertSame('GBP', $money->getCurrency());
    }

    public function testMoneyAddition(): void
    {
        $money1 = new Money('10.50', 'USD');
        $money2 = new Money('5.25', 'USD');
        
        $result = $money1->add($money2);
        
        self::assertSame('15.75', $result->getAmount());
        self::assertSame('USD', $result->getCurrency());
    }

    public function testMoneySubtraction(): void
    {
        $money1 = new Money('10.50', 'USD');
        $money2 = new Money('5.25', 'USD');
        
        $result = $money1->subtract($money2);
        
        self::assertSame('5.25', $result->getAmount());
    }

    public function testInvalidAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');
        
        new Money('-10.00', 'USD');
    }

    public function testInvalidCurrency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency must be 3 characters long');
        
        new Money('10.00', 'US');
    }

    public function testDifferentCurrencyOperation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot perform operation on different currencies');
        
        $money1 = new Money('10.00', 'USD');
        $money2 = new Money('10.00', 'EUR');
        
        $money1->add($money2);
    }
}