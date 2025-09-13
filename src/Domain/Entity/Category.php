<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Slug;
use App\Infrastructure\Doctrine\Type\SlugType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Product category entity
 * 
 * Represents product categories with hierarchical structure support.
 */
#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\HasLifecycleCallbacks]
final class Category extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Category name must be at least {{ limit }} characters',
        maxMessage: 'Category name cannot be longer than {{ limit }} characters'
    )]
    private string $name;

    #[ORM\Column(type: SlugType::NAME, length: 120, unique: true)]
    private Slug $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Sort order must be zero or positive')]
    private int $sortOrder = 0;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'name' => 'ASC'])]
    private Collection $children;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)]
    private Collection $products;

    public function __construct(string $name, Slug $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function setSlug(Slug $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlugString(): string
    {
        return $this->slug->getValue();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Category $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(Category $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }
        return $this;
    }

    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent !== null) {
            $level++;
            $parent = $parent->getParent();
        }
        
        return $level;
    }

    public function getPath(): string
    {
        $path = [$this->slug->getValue()];
        $parent = $this->parent;
        
        while ($parent !== null) {
            array_unshift($path, $parent->getSlugString());
            $parent = $parent->getParent();
        }
        
        return implode('/', $path);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}