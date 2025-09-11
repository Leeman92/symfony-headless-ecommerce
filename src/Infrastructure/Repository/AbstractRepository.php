<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\RepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * Find entities by criteria with intentional performance issues for Phase 1
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e');
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $qb->andWhere("e.{$field} = :{$field}")
                   ->setParameter($field, $value);
            }
        }
        
        return $qb->getQuery()->getResult();
    }
}