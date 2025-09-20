<?php

declare(strict_types=1);

namespace App\Tests\Support\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

abstract class DoctrineRepositoryTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected InMemoryManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = TestEntityManagerFactory::create();
        $this->managerRegistry = new InMemoryManagerRegistry($this->entityManager);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [];

        foreach ($this->schemaClasses() as $class) {
            $metadata[] = $this->entityManager->getClassMetadata($class);
        }

        if ($metadata !== []) {
            $schemaTool->dropDatabase();
            $schemaTool->createSchema($metadata);
        }
    }

    /**
     * @return list<class-string>
     */
    abstract protected function schemaClasses(): array;

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }

        unset($this->entityManager, $this->managerRegistry);

        parent::tearDown();
    }
}
