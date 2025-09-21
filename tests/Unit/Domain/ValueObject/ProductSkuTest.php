<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\ProductSku;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Unit tests for ProductSku value object
 */
final class ProductSkuTest extends TestCase
{
    public function testProductSkuCreation(): void
    {
        $sku = new ProductSku('PROD-123-ABC');

        self::assertSame('PROD-123-ABC', $sku->getValue());
        self::assertSame('PROD-123-ABC', (string) $sku);
    }

    public function testProductSkuNormalization(): void
    {
        $sku = new ProductSku('  prod-123-abc  ');

        self::assertSame('PROD-123-ABC', $sku->getValue());
    }

    public function testProductSkuGeneration(): void
    {
        $sku = ProductSku::generate();

        self::assertStringStartsWith('SKU-', $sku->getValue());
        self::assertMatchesRegularExpression('/^SKU-\d+-\d{4}$/', $sku->getValue());
    }

    public function testProductSkuGenerationWithCustomPrefix(): void
    {
        $sku = ProductSku::generate('CUSTOM');

        self::assertStringStartsWith('CUSTOM-', $sku->getValue());
    }

    public function testProductSkuFromProductName(): void
    {
        $sku = ProductSku::fromProductName('Wireless Bluetooth Headphones');

        self::assertStringStartsWith('WIRELESS-BLUETOOTH-HEADPHONES-', $sku->getValue());
        self::assertMatchesRegularExpression('/^WIRELESS-BLUETOOTH-HEADPHONES-\d{3}$/', $sku->getValue());
    }

    public function testProductSkuFromLongProductName(): void
    {
        $longName = 'This is a very long product name that exceeds thirty characters';
        $sku = ProductSku::fromProductName($longName);

        // Should be truncated to 30 characters plus random suffix
        self::assertLessThanOrEqual(50, strlen($sku->getValue()));
        self::assertStringContainsString('-', $sku->getValue());
    }

    public function testProductSkuPrefixExtraction(): void
    {
        $sku = new ProductSku('PROD-123-ABC');

        self::assertSame('PROD', $sku->getPrefix());
    }

    public function testProductSkuWithoutPrefix(): void
    {
        $sku = new ProductSku('SIMPLESKU');

        self::assertNull($sku->getPrefix());
    }

    public function testProductSkuEquality(): void
    {
        $sku1 = new ProductSku('PROD-123-ABC');
        $sku2 = new ProductSku('PROD-123-ABC');
        $sku3 = new ProductSku('PROD-456-DEF');

        self::assertTrue($sku1->equals($sku2));
        self::assertFalse($sku1->equals($sku3));
    }

    public function testEmptyProductSku(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU cannot be empty');

        new ProductSku('');
    }

    public function testProductSkuTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU cannot be longer than 50 characters');

        $longSku = str_repeat('A', 51);
        new ProductSku($longSku);
    }

    public function testInvalidProductSkuFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU can only contain uppercase letters, numbers, hyphens, and underscores');

        new ProductSku('PROD@123');
    }
}
