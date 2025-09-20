<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Category;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\Product;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrderItem entity
 */
final class OrderItemTest extends TestCase
{
    private Product $product;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        $category = new Category('Electronics', Slug::fromString('electronics'));
        $slug = Slug::fromString('test-product');
        $price = new Money('99.99', 'USD');
        $this->product = new Product('Test Product', $slug, $price, $category);
        $this->product->setSku(new ProductSku('TEST-SKU-001'));
        
        $this->orderItem = new OrderItem($this->product, 2);
    }

    public function testOrderItemCreation(): void
    {
        self::assertSame($this->product, $this->orderItem->getProduct());
        self::assertSame('Test Product', $this->orderItem->getProductName());
        self::assertInstanceOf(ProductSku::class, $this->orderItem->getProductSku());
        self::assertSame('TEST-SKU-001', $this->orderItem->getProductSku()?->getValue());
        self::assertSame('99.99', $this->orderItem->getUnitPrice()->getAmount());
        self::assertSame(99.99, $this->orderItem->getUnitPriceAsFloat());
        self::assertSame(2, $this->orderItem->getQuantity());
        self::assertSame('199.98', $this->orderItem->getTotalPrice()->getAmount());
        self::assertSame(199.98, $this->orderItem->getTotalPriceAsFloat());
    }

    public function testOrderItemWithCustomPrice(): void
    {
        $orderItem = new OrderItem($this->product, 3, new Money('89.99', 'USD'));
        
        self::assertSame('89.99', $orderItem->getUnitPrice()->getAmount());
        self::assertSame(89.99, $orderItem->getUnitPriceAsFloat());
        self::assertSame(3, $orderItem->getQuantity());
        self::assertSame('269.97', $orderItem->getTotalPrice()->getAmount());
    }

    public function testOrderItemWithProductWithoutSku(): void
    {
        $category = new Category('Books', Slug::fromString('books'));
        $productWithoutSku = new Product('Book Title', Slug::fromString('book-title'), new Money('19.99'), $category);
        $orderItem = new OrderItem($productWithoutSku, 1);
        
        self::assertNull($orderItem->getProductSku());
        self::assertSame('Book Title', $orderItem->getProductName());
    }

    public function testQuantityUpdate(): void
    {
        $this->orderItem->setQuantity(5);
        
        self::assertSame(5, $this->orderItem->getQuantity());
        self::assertSame('499.95', $this->orderItem->getTotalPrice()->getAmount());
        self::assertSame(499.95, $this->orderItem->getTotalPriceAsFloat());
    }

    public function testUnitPriceUpdate(): void
    {
        $this->orderItem->setUnitPrice(new Money('79.99', 'USD'));
        
        self::assertSame('79.99', $this->orderItem->getUnitPrice()->getAmount());
        self::assertSame(79.99, $this->orderItem->getUnitPriceAsFloat());
        self::assertSame('159.98', $this->orderItem->getTotalPrice()->getAmount());
        self::assertSame(159.98, $this->orderItem->getTotalPriceAsFloat());
    }

    public function testManualTotalPriceCalculation(): void
    {
        $this->orderItem->calculateTotalPrice();
        
        // Should recalculate based on current unit price and quantity
        self::assertSame('199.98', $this->orderItem->getTotalPrice()->getAmount());
    }

    public function testOrderAssociation(): void
    {
        $order = new Order(new OrderNumber('ORD-2024-001'));
        
        self::assertNull($this->orderItem->getOrder());
        
        $this->orderItem->setOrder($order);
        
        self::assertSame($order, $this->orderItem->getOrder());
    }

    public function testProductNameUpdate(): void
    {
        $this->orderItem->setProductName('Updated Product Name');
        
        self::assertSame('Updated Product Name', $this->orderItem->getProductName());
        // Original product name should remain unchanged
        self::assertSame('Test Product', $this->product->getName());
    }

    public function testProductSkuUpdate(): void
    {
        $this->orderItem->setProductSku('NEW-SKU-001');
        
        self::assertSame('NEW-SKU-001', $this->orderItem->getProductSku()?->getValue());
        // Original product SKU should remain unchanged
        self::assertSame('TEST-SKU-001', $this->product->getSku()?->getValue());
    }

    public function testOrderItemValidation(): void
    {
        // OrderItem needs to be associated with an order to be valid
        $order = new Order(new OrderNumber('ORD-2024-001'));
        $this->orderItem->setOrder($order);
        
        // Test valid order item
        self::assertTrue($this->orderItem->isValid());
        self::assertEmpty($this->orderItem->getValidationErrors());
    }

    public function testOrderItemToString(): void
    {
        self::assertSame('Test Product x 2', (string) $this->orderItem);
        
        $this->orderItem->setQuantity(1);
        self::assertSame('Test Product x 1', (string) $this->orderItem);
        
        $this->orderItem->setProductName('Custom Product Name');
        self::assertSame('Custom Product Name x 1', (string) $this->orderItem);
    }

    public function testDecimalPrecisionHandling(): void
    {
        // Test with price that has more than 2 decimal places
        $this->orderItem->setUnitPrice('33.333');
        $this->orderItem->setQuantity(3);
        
        // Money normalizes to 2 decimal places, truncating extra precision
        self::assertSame('99.99', $this->orderItem->getTotalPrice()->getAmount());
    }

    public function testZeroQuantityHandling(): void
    {
        $this->orderItem->setQuantity(0);
        
        self::assertSame(0, $this->orderItem->getQuantity());
        self::assertSame('0.00', $this->orderItem->getTotalPrice()->getAmount());
        self::assertSame(0.0, $this->orderItem->getTotalPriceAsFloat());
    }

    public function testLargeQuantityHandling(): void
    {
        $this->orderItem->setQuantity(1000);
        
        self::assertSame(1000, $this->orderItem->getQuantity());
        self::assertSame('99990.00', $this->orderItem->getTotalPrice()->getAmount());
        self::assertSame(99990.0, $this->orderItem->getTotalPriceAsFloat());
    }
}
