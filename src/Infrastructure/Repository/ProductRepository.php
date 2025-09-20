<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Product;
use App\Domain\Repository\ProductRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use function mb_strtolower;

final class ProductRepository extends AbstractRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findOneBySlug(string $slug): ?Product
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function searchProducts(?string $term, ?string $categorySlug, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC');

        if ($term !== null && $term !== '') {
            $normalized = '%' . mb_strtolower($term) . '%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(COALESCE(p.description, \'\')) LIKE :term OR LOWER(COALESCE(p.shortDescription, \'\')) LIKE :term')
                ->setParameter('term', $normalized);
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb->leftJoin('p.category', 'c')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        $this->applyPagination($qb, $page, $limit);

        $products = $qb->getQuery()->getResult();

        // Intentional N+1: accessing category details per product triggers extra queries in Phase 1
        foreach ($products as $product) {
            $product->getCategory()?->getName();
        }

        return $products;
    }

    public function countSearchResults(?string $term, ?string $categorySlug): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true);

        if ($term !== null && $term !== '') {
            $normalized = '%' . mb_strtolower($term) . '%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(COALESCE(p.description, \'\')) LIKE :term OR LOWER(COALESCE(p.shortDescription, \'\')) LIKE :term')
                ->setParameter('term', $normalized);
        }

        if ($categorySlug !== null && $categorySlug !== '') {
            $qb->leftJoin('p.category', 'c')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findFeaturedProducts(int $limit = 8): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isFeatured = :featured')
            ->setParameter('active', true)
            ->setParameter('featured', true)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(max(1, $limit));

        $products = $qb->getQuery()->getResult();

        // Intentional N+1: keep phase-one eager loading story consistent
        foreach ($products as $product) {
            $product->getCategory()?->getName();
        }

        return $products;
    }
}
