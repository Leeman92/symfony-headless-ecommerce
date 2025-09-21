<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Address;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Address value object
 */
final class AddressTest extends TestCase
{
    public function testAddressCreation(): void
    {
        $address = new Address(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'US',
        );

        self::assertSame('123 Main St', $address->getStreet());
        self::assertSame('New York', $address->getCity());
        self::assertSame('NY', $address->getState());
        self::assertSame('10001', $address->getPostalCode());
        self::assertSame('US', $address->getCountry());
    }

    public function testAddressFromArray(): void
    {
        $data = [
            'street' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90210',
            'country' => 'US',
        ];

        $address = Address::fromArray($data);

        self::assertSame('456 Oak Ave', $address->getStreet());
        self::assertSame('Los Angeles', $address->getCity());
        self::assertSame('CA', $address->getState());
        self::assertSame('90210', $address->getPostalCode());
        self::assertSame('US', $address->getCountry());
    }

    public function testAddressToArray(): void
    {
        $address = new Address(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'US',
        );

        $expected = [
            'street' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
        ];

        self::assertSame($expected, $address->toArray());
    }

    public function testFormattedAddress(): void
    {
        $address = new Address(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'US',
        );

        $expected = '123 Main St, New York, NY 10001, US';
        self::assertSame($expected, $address->getFormattedAddress());
        self::assertSame($expected, (string) $address);
    }

    public function testCountryCheck(): void
    {
        $address = new Address(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'US',
        );

        self::assertTrue($address->isInCountry('US'));
        self::assertTrue($address->isInCountry('us')); // Case insensitive
        self::assertFalse($address->isInCountry('CA'));
    }

    public function testAddressEquality(): void
    {
        $address1 = new Address('123 Main St', 'New York', 'NY', '10001', 'US');
        $address2 = new Address('123 Main St', 'New York', 'NY', '10001', 'US');
        $address3 = new Address('456 Oak Ave', 'Los Angeles', 'CA', '90210', 'US');

        self::assertTrue($address1->equals($address2));
        self::assertFalse($address1->equals($address3));
    }

    public function testEmptyStreet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Street address cannot be empty');

        new Address('', 'New York', 'NY', '10001', 'US');
    }

    public function testEmptyCity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('City cannot be empty');

        new Address('123 Main St', '', 'NY', '10001', 'US');
    }

    public function testInvalidCountryCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Country must be a 2-letter ISO code');

        new Address('123 Main St', 'New York', 'NY', '10001', 'USA');
    }

    public function testTooLongStreet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Street address cannot be longer than 255 characters');

        $longStreet = str_repeat('a', 256);
        new Address($longStreet, 'New York', 'NY', '10001', 'US');
    }
}
