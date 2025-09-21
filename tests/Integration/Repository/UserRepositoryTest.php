<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use App\Infrastructure\Repository\UserRepository;
use App\Tests\Support\Doctrine\DoctrineRepositoryTestCase;
use http\Exception\RuntimeException;

final class UserRepositoryTest extends DoctrineRepositoryTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        if (null === $this->managerRegistry) {
            throw new RuntimeException('ManagerRegistry cannot be null');
        }
        $this->repository = new UserRepository($this->managerRegistry);
    }

    protected function schemaClasses(): array
    {
        return [User::class];
    }

    public function testFindActiveUserByEmailReturnsUser(): void
    {
        $user = $this->createUser('active@example.com');

        $found = $this->repository->findActiveUserByEmail('active@example.com');

        self::assertNotNull($found);
        self::assertSame($user->getId(), $found->getId());
    }

    public function testFindActiveUserByEmailIgnoresInactive(): void
    {
        $this->createUser('inactive@example.com', active: false);

        $found = $this->repository->findActiveUserByEmail('inactive@example.com');

        self::assertNull($found);
    }

    public function testSearchCustomersFiltersByTerm(): void
    {
        $this->createUser('john@example.com', firstName: 'John', lastName: 'Smith');
        $this->createUser('jane@example.com', firstName: 'Jane', lastName: 'Doe');

        $results = $this->repository->searchCustomers('smith', 10);

        self::assertCount(1, $results);
        self::assertSame('john@example.com', $results[0]->getEmail()->getValue());
    }

    public function testFindAdminsReturnsOnlyActiveAdmins(): void
    {
        $admin = $this->createUser('admin@example.com', roles: ['ROLE_ADMIN']);
        $this->createUser('inactive-admin@example.com', roles: ['ROLE_ADMIN'], active: false);
        $this->createUser('user@example.com');

        $results = $this->repository->findAdmins();

        self::assertCount(1, $results);
        self::assertSame($admin->getId(), $results[0]->getId());
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(
        string $email,
        string $firstName = 'Test',
        string $lastName = 'User',
        array $roles = ['ROLE_USER'],
        bool $active = true,
    ): User {
        $user = new User(new Email($email), new PersonName($firstName, $lastName), '', 'hash');
        $user->setRoles($roles);
        $user->setIsActive($active);

        $this->entityManager?->persist($user);
        $this->entityManager?->flush();

        return $user;
    }
}
