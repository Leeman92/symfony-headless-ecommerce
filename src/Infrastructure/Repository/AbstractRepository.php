<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\RepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function is_array;
use function sprintf;
use function str_replace;
use function strtoupper;

/**
 * Abstract base repository with common functionality
 *
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository implements RepositoryInterface
{
    /**
     * @param class-string<T> $entityClass
     */
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
    /**
     * @param array<string, scalar|array<array-key, scalar>|null> $criteria
     * @param array<string, string|int|null> $sorting
     * @return list<T>
     */
    public function findByCriteria(array $criteria, array $sorting = []): array
    {
        $qb = $this->createQueryBuilder('entity');
        $this->applyCriteria($qb, $criteria);
        $this->applySorting($qb, $sorting);

        /** @var list<T> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>|null> $criteria
     * @param array<string, string|int|null> $sorting
     * @return T|null
     */
    public function findOneByCriteria(array $criteria, array $sorting = []): ?object
    {
        $qb = $this->createQueryBuilder('entity');
        $this->applyCriteria($qb, $criteria);
        $this->applySorting($qb, $sorting);

        /** @var T|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * Apply exact-match filters to a query builder
     *
     * @param array<string, scalar|array<array-key, scalar>|null> $criteria
     */
    protected function applyCriteria(QueryBuilder $qb, array $criteria, string $alias = 'entity'): void
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
     *
     * @param array<string, string|int|null> $sorting
     */
    protected function applySorting(QueryBuilder $qb, array $sorting, string $alias = 'entity'): void
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
