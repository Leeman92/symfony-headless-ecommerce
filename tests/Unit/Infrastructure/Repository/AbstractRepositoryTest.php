<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Domain\Entity\Category;
use App\Domain\ValueObject\Slug;
use App\Infrastructure\Repository\AbstractRepository;
use App\Tests\Support\Doctrine\DoctrineRepositoryTestCase;
use Doctrine\Persistence\ManagerRegistry;

final class AbstractRepositoryTest extends DoctrineRepositoryTestCase
{
    private CategoryRepositoryStub $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CategoryRepositoryStub($this->managerRegistry);
    }

    protected function schemaClasses(): array
    {
        return [
            Category::class,
        ];
    }

    public function testSavePersistsEntity(): void
    {
        $category = new Category('Electronics', new Slug('electronics'));

        $this->repository->save($category);

        self::assertNotNull($category->getId());

        $reloaded = $this->repository->find($category->getId());
        self::assertSame('Electronics', $reloaded->getName());
    }

    public function testFindByCriteriaReturnsMatchingEntities(): void
    {
        $active = new Category('Active', new Slug('active'));
        $active->setIsActive(true);
        $inactive = new Category('Inactive', new Slug('inactive'));
        $inactive->setIsActive(false);

        $this->repository->save($active);
        $this->repository->save($inactive);

        $results = $this->repository->exposeFindByCriteria(['isActive' => true]);

        self::assertCount(1, $results);
        self::assertSame('Active', $results[0]->getName());
    }

    public function testFindOneByCriteriaReturnsSingleEntity(): void
    {
        $category = new Category('Gear', new Slug('gear'));
        $this->repository->save($category);

        $found = $this->repository->exposeFindOneByCriteria(['slug' => new Slug('gear')]);

        self::assertNotNull($found);
        self::assertSame('Gear', $found->getName());
    }

    public function testRemoveDeletesEntity(): void
    {
        $category = new Category('Temporary', new Slug('temporary'));
        $this->repository->save($category);

        $categoryId = $category->getId();

        $this->repository->remove($category);

        self::assertNotNull($categoryId);
        self::assertNull($this->repository->find($categoryId));
    }
}

/** @extends AbstractRepository<Category> */
final class CategoryRepositoryStub extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** @return list<Category> */
    public function exposeFindByCriteria(array $criteria): array
    {
        return $this->findByCriteria($criteria);
    }

    public function exposeFindOneByCriteria(array $criteria): ?Category
    {
        return $this->findOneByCriteria($criteria);
    }
}
