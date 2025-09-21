<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use Doctrine\DBAL\LockMode;

/**
 * Base repository interface for common operations.
 */
interface RepositoryInterface
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    public function findAll();

    public function save($entity);

    public function remove($entity);
}
