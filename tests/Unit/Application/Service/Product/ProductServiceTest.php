<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service\Product;

use App\Application\Service\Product\ProductSearchCriteria;
use App\Application\Service\Product\ProductService;
use App\Application\Service\Product\ProductServiceInterface;
use App\Domain\Entity\Category;
use App\Domain\Entity\Product;
use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InvalidProductDataException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Slug;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ProductServiceTest extends TestCase
{
    private ProductRepositoryInterface&MockObject $repository;

    private ProductServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->service = new ProductService($this->repository);
    }

    public function testCreateProductPersistsValidProduct(): void
    {
        $product = $this->createProduct();

        $this->repository->expects(self::once())
            ->method('save')
            ->with($product);

        $result = $this->service->createProduct($product);

        self::assertSame($product, $result);
    }

    public function testCreateProductThrowsOnInvalidProduct(): void
    {
        $product = $this->createProduct();
        $product->setName('');

        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidProductDataException::class);

        $this->service->createProduct($product);
    }

    public function testUpdateProductPersistsChanges(): void
    {
        $product = $this->createPersistedProduct();
        $product->setName('Updated Name');

        $this->repository->expects(self::once())
            ->method('save')
            ->with($product);

        $updated = $this->service->updateProduct($product);

        self::assertSame('Updated Name', $updated->getName());
    }

    public function testUpdateProductThrowsWhenNotPersisted(): void
    {
        $product = $this->createProduct();

        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidArgumentException::class);

        $this->service->updateProduct($product);
    }

    public function testDeleteProductRemovesEntity(): void
    {
        $product = $this->createPersistedProduct();

        $this->repository->expects(self::once())
            ->method('find')
            ->with(10)
            ->willReturn($product);

        $this->repository->expects(self::once())
            ->method('remove')
            ->with($product);

        $this->service->deleteProduct(10);
    }

    public function testGetProductReturnsWhenExists(): void
    {
        $product = $this->createPersistedProduct();

        $this->repository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($product);

        $result = $this->service->getProduct(5);

        self::assertSame($product, $result);
    }

    public function testGetProductThrowsWhenMissing(): void
    {
        $this->repository->expects(self::once())
            ->method('find')
            ->with(404)
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        $this->service->getProduct(404);
    }

    public function testGetProductBySlugReturnsProduct(): void
    {
        $product = $this->createPersistedProduct();

        $this->repository->expects(self::once())
            ->method('findOneBySlug')
            ->with('gaming-laptop')
            ->willReturn($product);

        $result = $this->service->getProductBySlug('gaming-laptop');

        self::assertSame($product, $result);
    }

    public function testGetProductBySlugThrowsWhenMissing(): void
    {
        $this->repository->expects(self::once())
            ->method('findOneBySlug')
            ->with('missing-product')
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        $this->service->getProductBySlug('missing-product');
    }

    public function testSearchProductsReturnsResultWithMeta(): void
    {
        $criteria = new ProductSearchCriteria('laptop', null, 1, 10);
        $product = $this->createPersistedProduct();

        $this->repository->expects(self::once())
            ->method('searchProducts')
            ->with('laptop', null, 1, 10)
            ->willReturn([$product]);

        $this->repository->expects(self::once())
            ->method('countSearchResults')
            ->with('laptop', null)
            ->willReturn(1);

        $result = $this->service->searchProducts($criteria);

        self::assertSame([$product], $result->products());
        self::assertSame(1, $result->total());
        self::assertFalse($result->hasPreviousPage());
        self::assertFalse($result->hasNextPage());
    }

    public function testReserveStockDecreasesTrackedStock(): void
    {
        $product = $this->createPersistedProduct();
        $product->setStock(5);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(7)
            ->willReturn($product);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($product);

        $result = $this->service->reserveStock(7, 2);

        self::assertSame(3, $result->getStock());
    }

    public function testReserveStockThrowsWhenInsufficient(): void
    {
        $product = $this->createPersistedProduct();
        $product->setStock(1);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn($product);

        $this->repository->expects(self::never())->method('save');

        $this->expectException(InsufficientStockException::class);

        $this->service->reserveStock(99, 2);
    }

    public function testRestockProductIncreasesStock(): void
    {
        $product = $this->createPersistedProduct();
        $product->setStock(1);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(12)
            ->willReturn($product);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($product);

        $restocked = $this->service->restockProduct(12, 4);

        self::assertSame(5, $restocked->getStock());
    }

    public function testReserveStockRejectsInvalidQuantity(): void
    {
        $product = $this->createPersistedProduct();

        $this->repository->expects(self::once())
            ->method('find')
            ->with(3)
            ->willReturn($product);

        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidArgumentException::class);

        $this->service->reserveStock(3, 0);
    }

    private function createProduct(): Product
    {
        $category = new Category('Electronics', new Slug('electronics'));

        return new Product('Gaming Laptop', new Slug('gaming-laptop'), new Money('999.99'), $category);
    }

    private function createPersistedProduct(): Product
    {
        $product = $this->createProduct();

        $reflection = new ReflectionProperty(Product::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($product, 1);

        return $product;
    }
}
