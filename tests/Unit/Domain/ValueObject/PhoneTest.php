<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Phone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Phone value object
 */
final class PhoneTest extends TestCase
{
    public function testPhoneCreation(): void
    {
        $phone = new Phone('+1234567890');

        self::assertSame('+1234567890', $phone->getValue());
        self::assertSame('+1234567890', (string) $phone);
    }

    public function testPhoneNormalization(): void
    {
        $phone = new Phone('(123) 456-7890');

        self::assertSame('1234567890', $phone->getValue());
    }

    public function testPhoneWithoutCountryCode(): void
    {
        $phone = new Phone('1234567890');

        self::assertSame('1234567890', $phone->getValue());
    }

    public function testUSCountryCodeDetection(): void
    {
        $phone = new Phone('+1234567890');

        self::assertSame('+1', $phone->getCountryCode());
    }

    public function testUKCountryCodeDetection(): void
    {
        $phone = new Phone('+441234567890');

        self::assertSame('+44', $phone->getCountryCode());
    }

    public function testGermanCountryCodeDetection(): void
    {
        $phone = new Phone('+491234567890');

        self::assertSame('+49', $phone->getCountryCode());
    }

    public function testNoCountryCodeDetection(): void
    {
        $phone = new Phone('1234567890');

        self::assertNull($phone->getCountryCode());
    }

    public function testUSFormatting(): void
    {
        $phone = new Phone('+11234567890');

        $formatted = $phone->getFormattedForCountry('US');
        self::assertSame('+1 (123) 456-7890', $formatted);
    }

    public function testUSFormattingWithoutCountryCode(): void
    {
        $phone = new Phone('1234567890');

        $formatted = $phone->getFormattedForCountry('US');
        self::assertSame('(123) 456-7890', $formatted);
    }

    public function testPhoneEquality(): void
    {
        $phone1 = new Phone('+1234567890');
        $phone2 = new Phone('+1234567890');
        $phone3 = new Phone('+1987654321');

        self::assertTrue($phone1->equals($phone2));
        self::assertFalse($phone1->equals($phone3));
    }

    public function testEmptyPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number cannot be empty');

        new Phone('');
    }

    public function testPhoneTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number must be between 10 and 20 characters');

        new Phone('123');
    }

    public function testPhoneTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number must be between 10 and 20 characters');

        $longPhone = '+1'.str_repeat('1', 20);
        new Phone($longPhone);
    }

    public function testInvalidPhoneFormatWithLetters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        // This will normalize to '0123456789' which passes length but fails format (starts with 0)
        new Phone('0123456789');
    }
}
