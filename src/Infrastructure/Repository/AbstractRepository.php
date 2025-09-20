<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\RepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Abstract base repository with common functionality
 * Note: Type hints are minimal to maintain Doctrine compatibility
 */
abstract class AbstractRepository extends ServiceEntityRepository implements RepositoryInterface
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    public function save($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove($entity)
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Find entities by criteria with optional sorting
     * Note: intentionally simple to surface performance refactors later
     */
    public function findByCriteria(array $criteria, array $sorting = []): array
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyCriteria($qb, $criteria);
        $this->applySorting($qb, $sorting);

        return $qb->getQuery()->getResult();
    }

    public function findOneByCriteria(array $criteria, array $sorting = [])
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyCriteria($qb, $criteria);
        $this->applySorting($qb, $sorting);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Apply exact-match filters to a query builder
     */
    protected function applyCriteria(QueryBuilder $qb, array $criteria, string $alias = 'e'): void
    {
        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue;
            }

            $parameter = str_replace('.', '_', $field);

            if (is_array($value)) {
                $qb->andWhere(sprintf('%s.%s IN (:%s)', $alias, $field, $parameter))
                    ->setParameter($parameter, $value);
                continue;
            }

            $qb->andWhere(sprintf('%s.%s = :%s', $alias, $field, $parameter))
                ->setParameter($parameter, $value);
        }
    }

    /**
     * Apply order by clauses from an array of field => direction
     */
    protected function applySorting(QueryBuilder $qb, array $sorting, string $alias = 'e'): void
    {
        foreach ($sorting as $field => $direction) {
            $direction = strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->addOrderBy(sprintf('%s.%s', $alias, $field), $direction);
        }
    }

    /**
     * Apply simple pagination (1-based page index)
     */
    protected function applyPagination(QueryBuilder $qb, int $page, int $limit): void
    {
        $limit = max(1, $limit);
        $page = max(1, $page);

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
    }
}
