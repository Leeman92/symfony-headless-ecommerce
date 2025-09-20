<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findActiveUserByEmail(Email|string $email): ?User;

    /** @return list<User> */
    public function searchCustomers(?string $term, int $limit = 20): array;

    /** @return list<User> */
    public function findAdmins(): array;
}
