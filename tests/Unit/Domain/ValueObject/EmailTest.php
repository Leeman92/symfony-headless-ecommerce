<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email value object
 */
final class EmailTest extends TestCase
{
    public function testEmailCreation(): void
    {
        $email = new Email('user@example.com');

        self::assertSame('user@example.com', $email->getValue());
        self::assertSame('example.com', $email->getDomain());
        self::assertSame('user', $email->getLocalPart());
        self::assertFalse($email->isGmail());
    }

    public function testEmailNormalization(): void
    {
        $email = new Email('  USER@EXAMPLE.COM  ');

        self::assertSame('user@example.com', $email->getValue());
    }

    public function testGmailDetection(): void
    {
        $gmail = new Email('user@gmail.com');
        $other = new Email('user@example.com');

        self::assertTrue($gmail->isGmail());
        self::assertFalse($other->isGmail());
    }

    public function testEmailEquality(): void
    {
        $email1 = new Email('user@example.com');
        $email2 = new Email('user@example.com');
        $email3 = new Email('other@example.com');

        self::assertTrue($email1->equals($email2));
        self::assertFalse($email1->equals($email3));
    }

    public function testInvalidEmailFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new Email('invalid-email');
    }

    public function testEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('');
    }

    public function testEmailTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be longer than 180 characters');

        $longEmail = str_repeat('a', 170).'@example.com';
        new Email($longEmail);
    }

    public function testEmailToString(): void
    {
        $email = new Email('user@example.com');

        self::assertSame('user@example.com', (string) $email);
    }
}
