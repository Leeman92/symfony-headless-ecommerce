<?php

declare(strict_types=1);

namespace App\Application\Service\Product;

use App\Domain\Entity\Product;

interface ProductServiceInterface
{
    public function getProduct(int $productId): Product;

    public function getProductBySlug(string $slug): Product;

    public function createProduct(Product $product): Product;

    public function updateProduct(Product $product): Product;

    public function deleteProduct(int $productId): void;

    public function searchProducts(ProductSearchCriteria $criteria): ProductSearchResult;

    public function reserveStock(int $productId, int $quantity): Product;

    public function restockProduct(int $productId, int $quantity): Product;
}
