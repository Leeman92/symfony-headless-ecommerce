<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Product;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findOneBySlug(string $slug): ?Product;

    /** @return list<Product> */
    public function searchProducts(?string $term, ?string $categorySlug, int $page = 1, int $limit = 20): array;

    public function countSearchResults(?string $term, ?string $categorySlug): int;

    /** @return list<Product> */
    public function findFeaturedProducts(int $limit = 8): array;
}
