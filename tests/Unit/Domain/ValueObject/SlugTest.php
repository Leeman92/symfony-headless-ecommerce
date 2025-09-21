<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Slug;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Unit tests for Slug value object
 */
final class SlugTest extends TestCase
{
    public function testSlugCreation(): void
    {
        $slug = new Slug('my-awesome-product');

        self::assertSame('my-awesome-product', $slug->getValue());
        self::assertSame('my-awesome-product', (string) $slug);
    }

    public function testSlugFromString(): void
    {
        $slug = Slug::fromString('My Awesome Product!');

        self::assertSame('my-awesome-product', $slug->getValue());
    }

    public function testSlugFromStringWithSpecialCharacters(): void
    {
        $slug = Slug::fromString('Product @ $99.99 - Best Deal!');

        self::assertSame('product-99-99-best-deal', $slug->getValue());
    }

    public function testSlugFromStringWithConsecutiveSpaces(): void
    {
        $slug = Slug::fromString('Product    with    spaces');

        self::assertSame('product-with-spaces', $slug->getValue());
    }

    public function testSlugFromLongString(): void
    {
        $longText = str_repeat('Very Long Product Name ', 20);
        $slug = Slug::fromString($longText);

        self::assertLessThanOrEqual(220, strlen($slug->getValue()));
        self::assertStringEndsNotWith('-', $slug->getValue());
    }

    public function testSlugFromStringWithSuffix(): void
    {
        $slug = Slug::fromStringWithSuffix('My Product', '123');

        self::assertSame('my-product-123', $slug->getValue());
    }

    public function testSlugFromLongStringWithSuffix(): void
    {
        $longText = str_repeat('Very Long Product Name ', 20);
        $slug = Slug::fromStringWithSuffix($longText, 'suffix');

        self::assertLessThanOrEqual(220, strlen($slug->getValue()));
        self::assertStringEndsWith('-suffix', $slug->getValue());
    }

    public function testSlugWithSuffix(): void
    {
        $slug = new Slug('my-product');
        $newSlug = $slug->withSuffix('v2');

        self::assertSame('my-product-v2', $newSlug->getValue());
        self::assertSame('my-product', $slug->getValue()); // Original unchanged
    }

    public function testSlugWithPrefix(): void
    {
        $slug = new Slug('product');
        $newSlug = $slug->withPrefix('category');

        self::assertSame('category-product', $newSlug->getValue());
        self::assertSame('product', $slug->getValue()); // Original unchanged
    }

    public function testSlugEquality(): void
    {
        $slug1 = new Slug('my-product');
        $slug2 = new Slug('my-product');
        $slug3 = new Slug('other-product');

        self::assertTrue($slug1->equals($slug2));
        self::assertFalse($slug1->equals($slug3));
    }

    public function testEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty');

        new Slug('');
    }

    public function testSlugTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be longer than 220 characters');

        $longSlug = str_repeat('a', 221);
        new Slug($longSlug);
    }

    public function testSlugStartingWithHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot start or end with a hyphen');

        new Slug('-my-product');
    }

    public function testSlugEndingWithHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot start or end with a hyphen');

        new Slug('my-product-');
    }

    public function testSlugWithConsecutiveHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot contain consecutive hyphens');

        new Slug('my--product');
    }

    public function testSlugFromEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate slug from provided text');

        Slug::fromString('!@#$%^&*()');
    }

    public function testValidSlugWithUppercase(): void
    {
        // This should NOT throw an exception - uppercase gets normalized
        $slug = Slug::fromString('My-Product');

        self::assertSame('my-product', $slug->getValue());
    }
}
