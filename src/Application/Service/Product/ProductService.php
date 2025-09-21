<?php

declare(strict_types=1);

namespace App\Application\Service\Product;

use App\Domain\Entity\Product;
use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InvalidProductDataException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Repository\ProductRepositoryInterface;
use InvalidArgumentException;

final class ProductService implements ProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
    }

    public function getProduct(int $productId): Product
    {
        $product = $this->productRepository->find($productId);

        if (!$product instanceof Product) {
            throw new ProductNotFoundException($productId);
        }

        return $product;
    }

    public function getProductBySlug(string $slug): Product
    {
        $product = $this->productRepository->findOneBySlug($slug);

        if (!$product instanceof Product) {
            throw new ProductNotFoundException($slug);
        }

        return $product;
    }

    public function createProduct(Product $product): Product
    {
        $this->assertValidProduct($product);
        $this->productRepository->save($product);

        return $product;
    }

    public function updateProduct(Product $product): Product
    {
        if (!$product->isPersisted()) {
            throw new InvalidArgumentException('Cannot update a product that has not been persisted yet.');
        }

        $this->assertValidProduct($product);
        $this->productRepository->save($product);

        return $product;
    }

    public function deleteProduct(int $productId): void
    {
        $product = $this->getProduct($productId);
        $this->productRepository->remove($product);
    }

    public function searchProducts(ProductSearchCriteria $criteria): ProductSearchResult
    {
        $products = $this->productRepository->searchProducts(
            $criteria->term(),
            $criteria->categorySlug(),
            $criteria->page(),
            $criteria->limit(),
        );

        $total = $this->productRepository->countSearchResults(
            $criteria->term(),
            $criteria->categorySlug(),
        );

        return new ProductSearchResult($products, $total, $criteria->page(), $criteria->limit());
    }

    public function reserveStock(int $productId, int $quantity): Product
    {
        $product = $this->getProduct($productId);
        $quantity = $this->guardQuantity($quantity);

        if ($product->isTrackStock() && $product->getStock() < $quantity) {
            throw new InsufficientStockException($productId, $quantity, $product->getStock());
        }

        if ($product->isTrackStock()) {
            $product->decreaseStock($quantity);
        }

        $this->productRepository->save($product);

        return $product;
    }

    public function restockProduct(int $productId, int $quantity): Product
    {
        $product = $this->getProduct($productId);
        $quantity = $this->guardQuantity($quantity);

        if ($product->isTrackStock()) {
            $product->increaseStock($quantity);
        }

        $this->productRepository->save($product);

        return $product;
    }

    private function assertValidProduct(Product $product): void
    {
        if (!$product->isValid()) {
            throw new InvalidProductDataException($product->getValidationErrors());
        }
    }

    private function guardQuantity(int $quantity): int
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        return $quantity;
    }
}
