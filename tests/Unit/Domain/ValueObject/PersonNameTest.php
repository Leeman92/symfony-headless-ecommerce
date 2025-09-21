<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\PersonName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PersonName value object
 */
final class PersonNameTest extends TestCase
{
    public function testValidNameCreation(): void
    {
        $name = new PersonName('John', 'Doe');

        self::assertSame('John', $name->getFirstName());
        self::assertSame('Doe', $name->getLastName());
        self::assertSame('John Doe', $name->getFullName());
        self::assertSame('John Doe', (string) $name);
    }

    public function testNameTrimming(): void
    {
        $name = new PersonName('  John  ', '  Doe  ');

        self::assertSame('John', $name->getFirstName());
        self::assertSame('Doe', $name->getLastName());
    }

    public function testGetInitials(): void
    {
        $name = new PersonName('John', 'Doe');

        self::assertSame('JD', $name->getInitials());
    }

    public function testGetInitialsWithLowercase(): void
    {
        $name = new PersonName('john', 'doe');

        self::assertSame('JD', $name->getInitials());
    }

    public function testEquals(): void
    {
        $name1 = new PersonName('John', 'Doe');
        $name2 = new PersonName('John', 'Doe');
        $name3 = new PersonName('Jane', 'Doe');

        self::assertTrue($name1->equals($name2));
        self::assertFalse($name1->equals($name3));
    }

    public function testEmptyFirstNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        new PersonName('', 'Doe');
    }

    public function testEmptyLastNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        new PersonName('John', '');
    }

    public function testTooShortFirstNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name must be between 2 and 100 characters');

        new PersonName('J', 'Doe');
    }

    public function testTooLongFirstNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name must be between 2 and 100 characters');

        new PersonName(str_repeat('a', 101), 'Doe');
    }

    public function testTooShortLastNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name must be between 2 and 100 characters');

        new PersonName('John', 'D');
    }

    public function testTooLongLastNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name must be between 2 and 100 characters');

        new PersonName('John', str_repeat('a', 101));
    }
}
