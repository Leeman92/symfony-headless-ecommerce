<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Category;
use App\Domain\Entity\Product;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Slug;
use App\Infrastructure\Repository\ProductRepository;
use App\Tests\Support\Doctrine\DoctrineRepositoryTestCase;
use http\Exception\RuntimeException;

final class ProductRepositoryTest extends DoctrineRepositoryTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        if (null === $this->managerRegistry) {
            throw new RuntimeException('ManagerRegistry cannot be null');
        }

        $this->repository = new ProductRepository($this->managerRegistry);
    }

    protected function schemaClasses(): array
    {
        return [
            Category::class,
            Product::class,
        ];
    }

    public function testSearchProductsMatchesTerm(): void
    {
        $electronics = $this->createCategory('Electronics', 'electronics');
        $this->createProduct('Gaming Laptop', 'gaming-laptop', $electronics);
        $this->createProduct('Office Chair', 'office-chair', $electronics);

        $results = $this->repository->searchProducts('laptop', null, 1, 10);

        self::assertCount(1, $results);
        self::assertSame('Gaming Laptop', $results[0]->getName());
    }

    public function testSearchProductsFiltersByCategory(): void
    {
        $electronics = $this->createCategory('Electronics', 'electronics');
        $furniture = $this->createCategory('Furniture', 'furniture');

        $this->createProduct('Gaming Laptop', 'gaming-laptop', $electronics);
        $this->createProduct('Office Chair', 'office-chair', $furniture);

        $results = $this->repository->searchProducts(null, 'electronics', 1, 10);

        self::assertCount(1, $results);
        self::assertSame('Gaming Laptop', $results[0]->getName());
    }

    public function testSearchProductsAppliesPagination(): void
    {
        $category = $this->createCategory('Electronics', 'electronics');

        $this->createProduct('Product A', 'product-a', $category, '100.00');
        $this->createProduct('Product B', 'product-b', $category, '101.00');
        $this->createProduct('Product C', 'product-c', $category, '102.00');

        $results = $this->repository->searchProducts(null, null, 2, 2);

        self::assertCount(1, $results);
        self::assertInstanceOf(Product::class, $results[0]);
    }

    public function testCountSearchResultsReturnsTotal(): void
    {
        $category = $this->createCategory('Electronics', 'electronics');
        $this->createProduct('Gaming Laptop', 'gaming-laptop', $category);
        $this->createProduct('Gaming Mouse', 'gaming-mouse', $category);

        $count = $this->repository->countSearchResults('gaming', null);

        self::assertSame(2, $count);
    }

    public function testFindFeaturedProductsReturnsActiveFeatured(): void
    {
        $category = $this->createCategory('Electronics', 'electronics');

        $featured = $this->createProduct('Featured', 'featured', $category);
        $featured->setIsFeatured(true);

        $inactive = $this->createProduct('Inactive', 'inactive', $category);
        $inactive->setIsFeatured(true);
        $inactive->setIsActive(false);

        $results = $this->repository->findFeaturedProducts(5);

        self::assertCount(1, $results);
        self::assertSame('Featured', $results[0]->getName());
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category($name, new Slug($slug));
        $category->setDescription('Test category');

        $this->entityManager?->persist($category);
        $this->entityManager?->flush();

        return $category;
    }

    private function createProduct(
        string $name,
        string $slug,
        Category $category,
        string $price = '99.99',
    ): Product {
        $product = new Product($name, new Slug($slug), new Money($price), $category);
        $product->setShortDescription('Short description');

        $this->entityManager?->persist($product);
        $this->entityManager?->flush();

        return $product;
    }
}
