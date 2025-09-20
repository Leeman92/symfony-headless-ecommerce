<?php

declare(strict_types=1);

namespace App\Tests\Support\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

final class InMemoryManagerRegistry implements ManagerRegistry
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $managerName = 'default'
    ) {
    }

    public function getDefaultConnectionName(): string
    {
        return 'default';
    }

    public function getConnection(?string $name = null): object
    {
        return $this->entityManager->getConnection();
    }

    public function getConnections(): array
    {
        return ['default' => $this->getConnection()];
    }

    public function getConnectionNames(): array
    {
        return ['default' => 'default'];
    }

    public function getDefaultManagerName(): string
    {
        return $this->managerName;
    }

    public function getManager(?string $name = null): ObjectManager
    {
        return $this->entityManager;
    }

    public function getManagers(): array
    {
        return [$this->managerName => $this->entityManager];
    }

    public function resetManager(?string $name = null): ObjectManager
    {
        $this->entityManager->clear();
        return $this->entityManager;
    }

    public function getManagerNames(): array
    {
        return [$this->managerName => $this->managerName];
    }

    public function getRepository(string $persistentObject, ?string $persistentManagerName = null)
    {
        return $this->entityManager->getRepository($persistentObject);
    }

    public function getManagerForClass(string $class): ?ObjectManager
    {
        return $this->entityManager;
    }
}
