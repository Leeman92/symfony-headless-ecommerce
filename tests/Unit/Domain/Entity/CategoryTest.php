<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Category;
use App\Domain\ValueObject\Slug;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Category entity
 */
final class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        $slug = Slug::fromString('electronics');
        $this->category = new Category('Electronics', $slug);
    }

    public function testCategoryCreation(): void
    {
        self::assertSame('Electronics', $this->category->getName());
        self::assertSame('electronics', $this->category->getSlugString());
        self::assertInstanceOf(Slug::class, $this->category->getSlug());
        self::assertTrue($this->category->isActive());
        self::assertSame(0, $this->category->getSortOrder());
        self::assertNull($this->category->getParent());
        self::assertTrue($this->category->isRoot());
        self::assertFalse($this->category->hasChildren());
    }

    public function testHierarchicalStructure(): void
    {
        $parent = new Category('Electronics', Slug::fromString('electronics'));
        $child = new Category('Smartphones', Slug::fromString('smartphones'));

        $parent->addChild($child);

        self::assertTrue($parent->hasChildren());
        self::assertFalse($child->hasChildren());
        self::assertTrue($parent->isRoot());
        self::assertFalse($child->isRoot());
        self::assertSame($parent, $child->getParent());
        self::assertTrue($parent->getChildren()->contains($child));
    }

    public function testRemoveChild(): void
    {
        $parent = new Category('Electronics', Slug::fromString('electronics'));
        $child = new Category('Smartphones', Slug::fromString('smartphones'));

        $parent->addChild($child);
        self::assertTrue($parent->getChildren()->contains($child));

        $parent->removeChild($child);
        self::assertFalse($parent->getChildren()->contains($child));
        self::assertNull($child->getParent());
    }

    public function testGetLevel(): void
    {
        $root = new Category('Electronics', Slug::fromString('electronics'));
        $level1 = new Category('Mobile', Slug::fromString('mobile'));
        $level2 = new Category('Smartphones', Slug::fromString('smartphones'));

        $root->addChild($level1);
        $level1->addChild($level2);

        self::assertSame(0, $root->getLevel());
        self::assertSame(1, $level1->getLevel());
        self::assertSame(2, $level2->getLevel());
    }

    public function testGetPath(): void
    {
        $root = new Category('Electronics', Slug::fromString('electronics'));
        $level1 = new Category('Mobile', Slug::fromString('mobile'));
        $level2 = new Category('Smartphones', Slug::fromString('smartphones'));

        $root->addChild($level1);
        $level1->addChild($level2);

        self::assertSame('electronics', $root->getPath());
        self::assertSame('electronics/mobile', $level1->getPath());
        self::assertSame('electronics/mobile/smartphones', $level2->getPath());
    }

    public function testDescriptionHandling(): void
    {
        self::assertNull($this->category->getDescription());

        $this->category->setDescription('Category for electronic products');
        self::assertSame('Category for electronic products', $this->category->getDescription());
    }

    public function testSortOrderHandling(): void
    {
        self::assertSame(0, $this->category->getSortOrder());

        $this->category->setSortOrder(10);
        self::assertSame(10, $this->category->getSortOrder());
    }

    public function testActiveStatus(): void
    {
        self::assertTrue($this->category->isActive());

        $this->category->setIsActive(false);
        self::assertFalse($this->category->isActive());
    }

    public function testValidationWithValidData(): void
    {
        $category = new Category('Valid Category', Slug::fromString('valid-category'));

        self::assertTrue($category->isValid());
        self::assertEmpty($category->getValidationErrors());
    }

    public function testValidationWithInvalidData(): void
    {
        $category = new Category('', Slug::fromString('valid-slug')); // Empty name

        self::assertFalse($category->isValid());

        $errors = $category->getValidationErrors();
        self::assertArrayHasKey('name', $errors);
    }

    public function testSlugValidation(): void
    {
        // Test that fromString() converts invalid characters to valid slug
        $slug = Slug::fromString('invalid slug!');
        self::assertSame('invalid-slug', $slug->getValue());

        // Test direct constructor validation with invalid characters
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug can only contain lowercase letters, numbers, and hyphens');

        // This should throw an exception due to invalid slug format
        new Slug('invalid slug!');
    }

    public function testToString(): void
    {
        self::assertSame('Electronics', (string) $this->category);
    }
}
