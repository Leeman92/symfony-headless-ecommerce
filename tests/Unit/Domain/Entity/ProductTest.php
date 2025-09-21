<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Category;
use App\Domain\Entity\MediaAsset;
use App\Domain\Entity\Product;
use App\Domain\Entity\ProductMedia;
use App\Domain\Entity\ProductVariant;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Product entity
 */
final class ProductTest extends TestCase
{
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        $this->category = new Category('Electronics', Slug::fromString('electronics'));
        $this->product = new Product(
            'Test Product',
            Slug::fromString('test-product'),
            new Money('99.99'),
            $this->category,
        );
    }

    public function testProductCreation(): void
    {
        self::assertSame('Test Product', $this->product->getName());
        self::assertSame('test-product', $this->product->getSlug()->getValue());
        self::assertSame('99.99', $this->product->getPrice()->getAmount());
        self::assertSame(99.99, $this->product->getPriceAsFloat());
        self::assertSame($this->category, $this->product->getCategory());
        self::assertTrue($this->product->isActive());
        self::assertFalse($this->product->isFeatured());
        self::assertSame(0, $this->product->getStock());
    }

    public function testPriceHandling(): void
    {
        $this->product->setPrice(new Money('149.50'));

        self::assertSame('149.50', $this->product->getPrice()->getAmount());
        self::assertSame(149.50, $this->product->getPriceAsFloat());
    }

    public function testComparePriceAndDiscount(): void
    {
        self::assertNull($this->product->getComparePrice());
        self::assertNull($this->product->getComparePriceAsFloat());
        self::assertFalse($this->product->hasDiscount());
        self::assertNull($this->product->getDiscountPercentage());

        $this->product->setComparePrice(new Money('149.99'));

        self::assertSame('149.99', $this->product->getComparePrice()?->getAmount());
        self::assertSame(149.99, $this->product->getComparePriceAsFloat());
        self::assertTrue($this->product->hasDiscount());
        self::assertSame(33.34, $this->product->getDiscountPercentage());
    }

    public function testStockManagement(): void
    {
        self::assertSame(0, $this->product->getStock());
        self::assertFalse($this->product->isInStock());

        $this->product->setStock(10);
        self::assertSame(10, $this->product->getStock());
        self::assertTrue($this->product->isInStock());

        $this->product->increaseStock(5);
        self::assertSame(15, $this->product->getStock());

        $this->product->decreaseStock(7);
        self::assertSame(8, $this->product->getStock());

        $this->product->decreaseStock(20); // Should not go below 0
        self::assertSame(0, $this->product->getStock());
    }

    public function testStockTrackingDisabled(): void
    {
        $this->product->setTrackStock(false);
        $this->product->setStock(0);

        self::assertTrue($this->product->isInStock()); // Should be in stock when tracking disabled
    }

    public function testLowStockDetection(): void
    {
        $this->product->setStock(5);
        $this->product->setLowStockThreshold(3);

        self::assertFalse($this->product->isLowStock());

        $this->product->setStock(2);
        self::assertTrue($this->product->isLowStock());

        $this->product->setLowStockThreshold(null);
        self::assertFalse($this->product->isLowStock()); // No threshold set
    }

    public function testAttributeHandling(): void
    {
        self::assertNull($this->product->getAttributes());
        self::assertFalse($this->product->hasAttribute('color'));
        self::assertNull($this->product->getAttribute('color'));

        $this->product->setAttribute('color', 'red');
        self::assertTrue($this->product->hasAttribute('color'));
        self::assertSame('red', $this->product->getAttribute('color'));

        $this->product->setAttribute('size', 'large');
        $attributes = $this->product->getAttributes();
        self::assertSame(['color' => 'red', 'size' => 'large'], $attributes);

        $this->product->removeAttribute('color');
        self::assertFalse($this->product->hasAttribute('color'));
        self::assertTrue($this->product->hasAttribute('size'));
    }

    public function testVariantHandling(): void
    {
        self::assertSame(0, $this->product->getVariants()->count());

        $variantRed = new ProductVariant($this->product, new ProductSku('TEST-RED-M'));
        $variantRed->replaceAttributes(['color' => 'red', 'size' => 'M']);

        $variantBlue = new ProductVariant($this->product, new ProductSku('TEST-BLUE-L'));
        $variantBlue->replaceAttributes(['color' => 'blue', 'size' => 'L']);

        $this->product->addVariant($variantRed);
        $this->product->addVariant($variantBlue);

        self::assertSame(2, $this->product->getVariants()->count());
        self::assertSame('TEST-RED-M', $variantRed->getSku()->getValue());
        self::assertSame(['color' => 'red', 'size' => 'M'], $variantRed->getAttributeMap());
        self::assertSame($variantRed, $this->product->findVariantBySku('TEST-RED-M'));
    }

    public function testImageHandling(): void
    {
        self::assertSame([], $this->product->getImages());
        self::assertNull($this->product->getPrimaryMedia());

        $asset1 = new MediaAsset('/images/product1.jpg', 'Product image 1');
        $asset2 = new MediaAsset('/images/product2.jpg', 'Product image 2');

        $media1 = new ProductMedia($this->product, $asset1);
        $media1->setPosition(0);
        $media2 = new ProductMedia($this->product, $asset2);
        $media2->markAsPrimary(true);

        $this->product->addMedia($media1);
        $this->product->addMedia($media2);

        $images = $this->product->getImages();
        self::assertCount(2, $images);
        self::assertTrue($images[1]['is_primary']);
        self::assertSame('/images/product2.jpg', $images[1]['url']);

        $primaryMedia = $this->product->getPrimaryMedia();
        self::assertSame('/images/product2.jpg', $primaryMedia?->getMediaAsset()?->getUrl());
        $primaryImagePayload = $this->product->getPrimaryImage();
        self::assertNotNull($primaryImagePayload);
        self::assertSame('/images/product2.jpg', $primaryImagePayload['url']);
    }

    public function testPrimaryImageFallback(): void
    {
        $asset1 = new MediaAsset('/images/product1.jpg', 'Product image 1');
        $asset2 = new MediaAsset('/images/product2.jpg', 'Product image 2');

        $media1 = new ProductMedia($this->product, $asset1);
        $media1->markAsPrimary(false);
        $media2 = new ProductMedia($this->product, $asset2);
        $media2->markAsPrimary(false);

        $this->product->addMedia($media1);
        $this->product->addMedia($media2);

        $primaryMedia = $this->product->getPrimaryMedia();
        self::assertSame('/images/product1.jpg', $primaryMedia?->getMediaAsset()?->getUrl());
        $primaryImagePayload = $this->product->getPrimaryImage();
        self::assertNotNull($primaryImagePayload);
        self::assertSame('/images/product1.jpg', $primaryImagePayload['url']);
    }

    public function testSeoDataHandling(): void
    {
        self::assertNull($this->product->getSeoData());
        self::assertNull($this->product->getSeoTitle());
        self::assertNull($this->product->getSeoDescription());

        $this->product->setSeoTitle('SEO Title');
        $this->product->setSeoDescription('SEO Description');

        self::assertSame('SEO Title', $this->product->getSeoTitle());
        self::assertSame('SEO Description', $this->product->getSeoDescription());

        $seoData = $this->product->getSeoData();
        self::assertSame(['title' => 'SEO Title', 'description' => 'SEO Description'], $seoData);
    }

    public function testAvailabilityForPurchase(): void
    {
        $this->product->setIsActive(true);
        $this->product->setStock(0);
        self::assertFalse($this->product->isAvailableForPurchase());

        $this->product->setStock(5);
        self::assertTrue($this->product->isAvailableForPurchase());

        $this->product->setIsActive(false);
        self::assertFalse($this->product->isAvailableForPurchase());
    }

    public function testDescriptionHandling(): void
    {
        self::assertNull($this->product->getDescription());
        self::assertNull($this->product->getShortDescription());

        $this->product->setDescription('Full product description');
        $this->product->setShortDescription('Short description');

        self::assertSame('Full product description', $this->product->getDescription());
        self::assertSame('Short description', $this->product->getShortDescription());
    }

    public function testSkuHandling(): void
    {
        self::assertNull($this->product->getSku());

        $this->product->setSku(new ProductSku('PROD-001'));
        self::assertNotNull($this->product->getSku());
        self::assertSame('PROD-001', $this->product->getSku()->getValue());
    }

    public function testValidationWithValidData(): void
    {
        $category = new Category('Test Category', Slug::fromString('test-category'));
        $product = new Product(
            'Valid Product',
            Slug::fromString('valid-product'),
            new Money('29.99'),
            $category,
        );

        self::assertTrue($product->isValid());
        self::assertEmpty($product->getValidationErrors());
    }

    public function testValidationWithInvalidData(): void
    {
        $category = new Category('Test Category', Slug::fromString('test-category'));
        $product = new Product(
            '', // Empty name
            Slug::fromString('valid-product'),
            new Money('29.99'),
            $category,
        );

        self::assertFalse($product->isValid());

        $errors = $product->getValidationErrors();
        self::assertArrayHasKey('name', $errors);
    }

    public function testToString(): void
    {
        self::assertSame('Test Product', (string) $this->product);
    }
}
