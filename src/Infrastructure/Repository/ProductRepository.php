<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Product;
use App\Domain\Repository\ProductRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

use function mb_strtolower;

/**
 * @extends AbstractRepository<Product>
 */
final class ProductRepository extends AbstractRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findOneBySlug(string $slug): ?Product
    {
        /** @var Product|null $product */
        $product = $this->findOneBy(['slug' => $slug]);

        return $product;
    }

    /**
     * @return list<Product>
     */
    public function searchProducts(?string $term, ?string $categorySlug, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('product')
            ->andWhere('product.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('product.id', 'DESC');

        if ($term !== null && $term !== '') {
            $normalized = '%'.mb_strtolower($term).'%';
            $qb->andWhere('LOWER(product.name) LIKE :term OR LOWER(COALESCE(product.description, \'\')) LIKE :term OR LOWER(COALESCE(product.shortDescription, \'\')) LIKE :term')
                ->setParameter('term', $normalized);
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb->leftJoin('product.category', 'category')
                ->andWhere('category.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        $this->applyPagination($qb, $page, $limit);

        /** @var list<Product> $products */
        $products = $qb->getQuery()->getResult();

        // Intentional N+1: accessing category details per product triggers extra queries in Phase 1
        foreach ($products as $product) {
            $product->getCategory()?->getName();
        }

        return $products;
    }

    public function countSearchResults(?string $term, ?string $categorySlug): int
    {
        $qb = $this->createQueryBuilder('product')
            ->select('COUNT(product.id)')
            ->andWhere('product.isActive = :active')
            ->setParameter('active', true);

        if ($term !== null && $term !== '') {
            $normalized = '%'.mb_strtolower($term).'%';
            $qb->andWhere('LOWER(product.name) LIKE :term OR LOWER(COALESCE(product.description, \'\')) LIKE :term OR LOWER(COALESCE(product.shortDescription, \'\')) LIKE :term')
                ->setParameter('term', $normalized);
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb->leftJoin('product.category', 'category')
                ->andWhere('category.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Product>
     */
    public function findFeaturedProducts(int $limit = 8): array
    {
        $qb = $this->createQueryBuilder('product')
            ->andWhere('product.isActive = :active')
            ->andWhere('product.isFeatured = :featured')
            ->setParameter('active', true)
            ->setParameter('featured', true)
            ->orderBy('product.id', 'DESC')
            ->setMaxResults(max(1, $limit));

        /** @var list<Product> $products */
        $products = $qb->getQuery()->getResult();

        // Intentional N+1: keep phase-one eager loading story consistent
        foreach ($products as $product) {
            $product->getCategory()?->getName();
        }

        return $products;
    }
}
