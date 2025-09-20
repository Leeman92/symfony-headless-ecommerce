<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use Doctrine\Persistence\ManagerRegistry;
use function mb_strtolower;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveUserByEmail(Email|string $email): ?User
    {
        $value = $email instanceof Email ? $email->getValue() : (string) $email;

        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', mb_strtolower($value))
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchCustomers(?string $term, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(max(1, $limit));

        if ($term !== null && $term !== '') {
            $normalized = '%' . mb_strtolower($term) . '%';
            $qb->andWhere('LOWER(u.email) LIKE :term OR LOWER(u.name.firstName) LIKE :term OR LOWER(u.name.lastName) LIKE :term')
                ->setParameter('term', $normalized);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
