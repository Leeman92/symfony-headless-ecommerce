<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Type\JsonbType;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use App\Infrastructure\Doctrine\Type\MoneyType;
use App\Infrastructure\Doctrine\Type\ProductSkuType;
use App\Infrastructure\Doctrine\Type\SlugType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Product entity with PostgreSQL JSONB support
 * 
 * Represents e-commerce products with flexible attributes and variants
 * stored in PostgreSQL JSONB columns for optimal performance.
 */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
final class Product extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    #[ORM\Column(type: Types::STRING, length: 200)]
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: 'Product name must be at least {{ limit }} characters',
        maxMessage: 'Product name cannot be longer than {{ limit }} characters'
    )]
    private string $name;

    #[ORM\Column(type: SlugType::NAME, length: 220, unique: true)]
    private Slug $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $price;

    #[ORM\Column(type: MoneyType::NAME, nullable: true)]
    private ?Money $comparePrice = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Stock quantity must be zero or positive')]
    private int $stock = 0;

    #[ORM\Column(type: ProductSkuType::NAME, nullable: true)]
    private ?ProductSku $sku = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isFeatured = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $trackStock = true;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Low stock threshold must be positive')]
    private ?int $lowStockThreshold = null;

    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $attributes = null;

    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $variants = null;

    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $seoData = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Product must belong to a category')]
    private ?Category $category = null;

    public function __construct(
        string $name,
        Slug|string $slug,
        Money|string $price,
        Category $category
    ) {
        $this->name = $name;
        $this->slug = $slug instanceof Slug ? $slug : new Slug($slug);
        $this->price = $price instanceof Money ? $price : new Money($price);
        $this->category = $category;
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

    public function setSlug(Slug|string $slug): static
    {
        $this->slug = $slug instanceof Slug ? $slug : new Slug($slug);
        return $this;
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money|string $price): static
    {
        $this->price = $price instanceof Money ? $price : new Money($price);
        return $this;
    }

    public function getPriceAsFloat(): float
    {
        return $this->price->getAmountAsFloat();
    }

    public function getComparePrice(): ?Money
    {
        return $this->comparePrice;
    }

    public function setComparePrice(Money|string|null $comparePrice): static
    {
        if ($comparePrice === null) {
            $this->comparePrice = null;
        } elseif ($comparePrice instanceof Money) {
            $this->comparePrice = $comparePrice;
        } else {
            $this->comparePrice = new Money($comparePrice, $this->price->getCurrency());
        }
        return $this;
    }

    public function getComparePriceAsFloat(): ?float
    {
        return $this->comparePrice?->getAmountAsFloat();
    }

    public function hasDiscount(): bool
    {
        return $this->comparePrice !== null && 
               $this->comparePrice->isGreaterThan($this->price);
    }

    public function getDiscountPercentage(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        $original = $this->comparePrice->getAmountAsFloat();
        $current = $this->price->getAmountAsFloat();
        
        return round((($original - $current) / $original) * 100, 2);
    }

    public function calculateDiscountedPrice(float $discountPercentage): Money
    {
        return $this->price->multiply(1 - ($discountPercentage / 100));
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function increaseStock(int $quantity): static
    {
        $this->stock += $quantity;
        return $this;
    }

    public function decreaseStock(int $quantity): static
    {
        $this->stock = max(0, $this->stock - $quantity);
        return $this;
    }

    public function isInStock(): bool
    {
        return !$this->trackStock || $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->trackStock && 
               $this->lowStockThreshold !== null && 
               $this->stock <= $this->lowStockThreshold;
    }

    public function getSku(): ?ProductSku
    {
        return $this->sku;
    }

    public function setSku(ProductSku|string|null $sku): static
    {
        if ($sku === null) {
            $this->sku = null;
        } elseif ($sku instanceof ProductSku) {
            $this->sku = $sku;
        } else {
            $this->sku = new ProductSku($sku);
        }
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

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function isTrackStock(): bool
    {
        return $this->trackStock;
    }

    public function setTrackStock(bool $trackStock): static
    {
        $this->trackStock = $trackStock;
        return $this;
    }

    public function getLowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(?int $lowStockThreshold): static
    {
        $this->lowStockThreshold = $lowStockThreshold;
        return $this;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function setAttributes(?array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        if ($this->attributes === null) {
            $this->attributes = [];
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    public function removeAttribute(string $key): static
    {
        if ($this->attributes !== null && isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
        return $this;
    }

    public function hasAttribute(string $key): bool
    {
        return $this->attributes !== null && array_key_exists($key, $this->attributes);
    }

    public function getVariants(): ?array
    {
        return $this->variants;
    }

    public function setVariants(?array $variants): static
    {
        $this->variants = $variants;
        return $this;
    }

    public function addVariant(array $variant): static
    {
        if ($this->variants === null) {
            $this->variants = [];
        }
        $this->variants[] = $variant;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;
        return $this;
    }

    public function addImage(string $url, ?string $alt = null, bool $isPrimary = false): static
    {
        if ($this->images === null) {
            $this->images = [];
        }
        
        $this->images[] = [
            'url' => $url,
            'alt' => $alt,
            'is_primary' => $isPrimary,
        ];
        
        return $this;
    }

    public function getPrimaryImage(): ?array
    {
        if ($this->images === null) {
            return null;
        }

        foreach ($this->images as $image) {
            if ($image['is_primary'] ?? false) {
                return $image;
            }
        }

        return $this->images[0] ?? null;
    }

    public function getSeoData(): ?array
    {
        return $this->seoData;
    }

    public function setSeoData(?array $seoData): static
    {
        $this->seoData = $seoData;
        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoData['title'] ?? null;
    }

    public function setSeoTitle(?string $title): static
    {
        if ($this->seoData === null) {
            $this->seoData = [];
        }
        $this->seoData['title'] = $title;
        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoData['description'] ?? null;
    }

    public function setSeoDescription(?string $description): static
    {
        if ($this->seoData === null) {
            $this->seoData = [];
        }
        $this->seoData['description'] = $description;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function isAvailableForPurchase(): bool
    {
        return $this->isActive && $this->isInStock();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}